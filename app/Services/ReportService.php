<?php

namespace App\Services;

use App\Models\CashAccount;
use App\Models\ClientCompany;
use App\Models\Contract;
use App\Models\PaymentVoucher;
use App\Models\PayrollRun;
use App\Models\PurchaseInvoice;
use App\Models\ReceiptVoucher;
use App\Models\SalesInvoice;
use App\Models\Supplier;
use Illuminate\Support\Carbon;

class ReportService
{
    /**
     * ملخص مالي عام لفترة محددة: المبيعات المفوترة والمحصلة، المشتريات والمدفوعة،
     * الرواتب المصروفة، المصاريف التشغيلية الأخرى، وصافي التدفق النقدي التقريبي
     */
    public function financialSummary(Carbon $from, Carbon $to): array
    {
        $salesInvoiced = SalesInvoice::whereBetween('invoice_date', [$from, $to])
            ->where('status', '!=', 'cancelled')->sum('total_amount');

        $salesCollected = ReceiptVoucher::whereBetween('voucher_date', [$from, $to])
            ->where('status', 'confirmed')->sum('amount');

        $purchaseInvoiced = PurchaseInvoice::whereBetween('invoice_date', [$from, $to])
            ->where('status', '!=', 'cancelled')->sum('total_amount');

        $paymentsBase = PaymentVoucher::whereBetween('voucher_date', [$from, $to])->where('status', 'confirmed');

        $supplierPayments = (clone $paymentsBase)->where('purpose', 'supplier_payment')->sum('amount');
        $salariesPaid = (clone $paymentsBase)->where('purpose', 'salary')->sum('amount');
        $otherExpenses = (clone $paymentsBase)->where('purpose', 'expense')->sum('amount');
        $otherPayments = (clone $paymentsBase)->where('purpose', 'other')->sum('amount');

        $expensesByCategory = (clone $paymentsBase)->where('purpose', 'expense')
            ->selectRaw('cost_category, SUM(amount) as total')
            ->groupBy('cost_category')
            ->pluck('total', 'cost_category');

        $totalCashOut = $supplierPayments + $salariesPaid + $otherExpenses + $otherPayments;

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'sales_invoiced' => (float) $salesInvoiced,
            'sales_collected' => (float) $salesCollected,
            'purchase_invoiced' => (float) $purchaseInvoiced,
            'supplier_payments' => (float) $supplierPayments,
            'salaries_paid' => (float) $salariesPaid,
            'other_expenses' => (float) $otherExpenses,
            'other_payments' => (float) $otherPayments,
            'expenses_by_category' => $expensesByCategory,
            'net_cash_flow' => (float) $salesCollected - (float) $totalCashOut,
            'cash_accounts_balance' => (float) CashAccount::sum('current_balance'),
        ];
    }

    /** كشف حساب شركة عميلة: كل الفواتير والتحصيلات مرتبة زمنيًا مع رصيد متحرك */
    public function clientStatement(ClientCompany $company): array
    {
        $invoices = $company->salesInvoices()->where('status', '!=', 'cancelled')->get()->map(fn($i) => [
            'date' => $i->invoice_date,
            'type' => 'invoice',
            'reference' => $i->invoice_number,
            'debit' => (float) $i->total_amount, // مستحق على العميل
            'credit' => 0,
        ]);

        $receipts = ReceiptVoucher::where('client_company_id', $company->id)
            ->where('status', 'confirmed')->get()->map(fn($r) => [
                'date' => $r->voucher_date,
                'type' => 'receipt',
                'reference' => $r->voucher_number,
                'debit' => 0,
                'credit' => (float) $r->amount, // محصَّل من العميل
            ]);

        $rows = $invoices->concat($receipts)->sortBy('date')->values();

        $balance = 0;
        $rows = $rows->map(function ($row) use (&$balance) {
            $balance += $row['debit'] - $row['credit'];
            $row['running_balance'] = $balance;
            return $row;
        });

        return [
            'company' => $company,
            'rows' => $rows,
            'total_due' => $company->unpaidInvoicesTotal(),
        ];
    }

    /** كشف حساب مورد: كل فواتير الشراء والمدفوعات مرتبة زمنيًا مع رصيد متحرك */
    public function supplierStatement(Supplier $supplier): array
    {
        $invoices = $supplier->purchaseInvoices()->where('status', '!=', 'cancelled')->get()->map(fn($i) => [
            'date' => $i->invoice_date,
            'type' => 'invoice',
            'reference' => $i->invoice_number,
            'credit' => 0,
            'debit' => (float) $i->total_amount, // مستحق للمورد (علينا)
        ]);

        $payments = PaymentVoucher::where('supplier_id', $supplier->id)
            ->where('status', 'confirmed')->get()->map(fn($p) => [
                'date' => $p->voucher_date,
                'type' => 'payment',
                'reference' => $p->voucher_number,
                'debit' => 0,
                'credit' => (float) $p->amount, // مدفوع للمورد
            ]);

        $rows = $invoices->concat($payments)->sortBy('date')->values();

        $balance = 0;
        $rows = $rows->map(function ($row) use (&$balance) {
            $balance += $row['debit'] - $row['credit'];
            $row['running_balance'] = $balance;
            return $row;
        });

        return [
            'supplier' => $supplier,
            'rows' => $rows,
            'total_due' => $supplier->purchaseInvoices()->unpaid()->sum('total_amount'),
        ];
    }

    /** تقرير أداء العقود: الإيراد الشهري المتوقع لكل عقد نشط، والعقود القريبة من الانتهاء */
    public function contractsPerformance(): array
    {
        $active = Contract::active()->with('clientCompany', 'contractSites.site')->get()->map(fn($c) => [
            'contract_number' => $c->contract_number,
            'client' => $c->clientCompany->name,
            'type' => $c->contract_type,
            'billing_cycle' => $c->billing_cycle,
            'monthly_value' => (float) $c->calculatedTotal(),
            'guards_count' => $c->totalGuardsRequired(),
            'end_date' => $c->end_date?->toDateString() ?? 'مفتوح',
        ]);

        $expiringSoon = Contract::expiringSoon(30)->with('clientCompany')->get();

        return [
            'active_contracts' => $active,
            'total_monthly_revenue' => $active->sum('monthly_value'),
            'expiring_soon' => $expiringSoon,
        ];
    }

    /** ملخص تشغيل رواتب شهر معين */
    public function payrollSummary(int $month, int $year): ?array
    {
        $run = PayrollRun::where('month', $month)->where('year', $year)
            ->with('items.employee')->first();

        if (!$run) {
            return null;
        }

        return [
            'run' => $run,
            'total_base' => $run->items->sum('base_salary'),
            'total_allowances' => $run->items->sum('allowances'),
            'total_advance_deductions' => $run->items->sum('advance_deduction'),
            'total_other_deductions' => $run->items->sum('other_deductions'),
            'total_net' => $run->items->sum('net_salary'),
            'paid_count' => $run->items->where('is_paid', true)->count(),
            'unpaid_count' => $run->items->where('is_paid', false)->count(),
        ];
    }
}
