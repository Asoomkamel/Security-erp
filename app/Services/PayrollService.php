<?php

namespace App\Services;

use App\Models\CashAccount;
use App\Models\Employee;
use App\Models\PaymentVoucher;
use App\Models\PayrollRun;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    /**
     * إنشاء تشغيل رواتب جديد لشهر/سنة معينين، وتوليد بند لكل موظف نشط تلقائيًا
     * (الراتب الأساسي + خصم قسط السلفة النشطة إن وُجدت)
     */
    public function createRun(int $month, int $year): PayrollRun
    {
        $existing = PayrollRun::where('month', $month)->where('year', $year)->exists();
        if ($existing) {
            throw new \RuntimeException("يوجد تشغيل رواتب لشهر {$month}-{$year} بالفعل.");
        }

        return DB::transaction(function () use ($month, $year) {
            $run = PayrollRun::create([
                'month' => $month,
                'year' => $year,
                'status' => 'draft',
            ]);

            Employee::where('status', 'active')->with('activeAdvances')->each(function (Employee $employee) use ($run, $month, $year) {
                $advance = $employee->activeAdvances->first(); // أبسط افتراض: سلفة نشطة واحدة كحد أقصى في نفس الوقت
                $advanceDeduction = $advance ? $advance->nextInstallmentAmount() : 0;

                // مستفاد من مراجعة نسخة ثانية: حساب غياب وإضافي حقيقيين من سجلات الحضور الفعلية
                $attendance = $employee->attendances()->forMonth($month, $year)->get();
                $absenceDays = $attendance->where('status', 'absent')->count();
                $overtimeHours = (float) $attendance->sum('overtime_hours');

                $dailyRate = $employee->base_salary > 0 ? $employee->base_salary / 30 : 0;
                $hourlyRate = $dailyRate / 8;
                $overtimeMultiplier = (float) \App\Models\SystemSetting::get('overtime_multiplier', '1.5');

                $run->items()->create([
                    'employee_id' => $employee->id,
                    'base_salary' => $employee->base_salary,
                    'allowances' => 0,
                    'housing_allowance' => $employee->housing_allowance ?? 0,
                    'transport_allowance' => $employee->transport_allowance ?? 0,
                    'food_allowance' => $employee->food_allowance ?? 0,
                    'overtime_hours' => $overtimeHours,
                    'overtime_amount' => round($overtimeHours * $hourlyRate * $overtimeMultiplier, 2),
                    'advance_deduction' => $advanceDeduction,
                    'absence_days' => $absenceDays,
                    'absence_deduction' => round($absenceDays * $dailyRate, 2),
                    'other_deductions' => 0,
                ]);
            });

            return $run->fresh('items');
        });
    }

    /** اعتماد التشغيل: يقفل تعديل البنود ويجعله جاهزًا للصرف */
    public function approve(PayrollRun $run, int $approvedByUserId): void
    {
        $run->update([
            'status' => 'approved',
            'approved_by' => $approvedByUserId,
            'approved_at' => now(),
        ]);
    }

    /**
     * صرف راتب موظف واحد ضمن تشغيل معتمد: ينشئ سند صرف تلقائيًا،
     * يخصم من رصيد السلفة النشطة، ويحدّث حالة البند والتشغيل
     */
    public function payItem(\App\Models\PayrollItem $item, CashAccount $cashAccount, ?int $createdByUserId = null): PaymentVoucher
    {
        if ($item->payrollRun->status !== 'approved' && $item->payrollRun->status !== 'paid') {
            throw new \RuntimeException('لا يمكن صرف راتب من تشغيل لم يُعتمد بعد.');
        }

        if ($item->is_paid) {
            throw new \RuntimeException('تم صرف راتب هذا الموظف مسبقًا في هذا التشغيل.');
        }

        if ($item->net_salary > $cashAccount->current_balance) {
            throw new \RuntimeException("رصيد \"{$cashAccount->name}\" لا يكفي لصرف هذا الراتب.");
        }

        return DB::transaction(function () use ($item, $cashAccount, $createdByUserId) {
            $voucher = PaymentVoucher::create([
                'cash_account_id' => $cashAccount->id,
                'employee_id' => $item->employee_id,
                'voucher_number' => $this->nextVoucherNumber(),
                'voucher_date' => now()->toDateString(),
                'amount' => $item->net_salary,
                'payment_method' => 'bank_transfer',
                'purpose' => 'salary',
                'paid_to' => $item->employee->full_name,
                'notes' => $item->payrollRun->label(),
                'status' => 'confirmed',
                'created_by' => $createdByUserId,
            ]);

            $voucher->load('cashAccount');
            $voucher->applyEffects(); // تنقيص رصيد الصندوق فقط (لا توجد فاتورة شراء مرتبطة هنا)

            $item->update(['is_paid' => true, 'payment_voucher_id' => $voucher->id]);

            // خصم قسط السلفة الفعلي من رصيدها (إن وُجد خصم بهذا البند)
            if ($item->advance_deduction > 0) {
                $advance = $item->employee->activeAdvances()->first();
                $advance?->applyInstallment((float) $item->advance_deduction);
            }

            $item->payrollRun->refreshPaidStatus();

            return $voucher;
        });
    }

    /** صرف كل البنود غير المدفوعة في التشغيل دفعة واحدة من نفس الصندوق */
    public function payAll(PayrollRun $run, CashAccount $cashAccount, ?int $createdByUserId = null): array
    {
        $vouchers = [];

        foreach ($run->items()->where('is_paid', false)->get() as $item) {
            $vouchers[] = $this->payItem($item, $cashAccount, $createdByUserId);
        }

        return $vouchers;
    }

    private function nextVoucherNumber(): string
    {
        $year = now()->format('Y');
        return \App\Models\NumberSequence::next("PAYMENT_VOUCHER_{$year}", "PV-{$year}");
    }
}
