<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_run_id', 'employee_id', 'base_salary', 'allowances',
        'housing_allowance', 'transport_allowance', 'food_allowance',
        'overtime_hours', 'overtime_amount', 'bonus',
        'advance_deduction', 'absence_days', 'absence_deduction', 'other_deductions', 'net_salary',
        'is_paid', 'payment_voucher_id', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'base_salary' => 'decimal:2',
            'allowances' => 'decimal:2',
            'housing_allowance' => 'decimal:2',
            'transport_allowance' => 'decimal:2',
            'food_allowance' => 'decimal:2',
            'overtime_hours' => 'decimal:2',
            'overtime_amount' => 'decimal:2',
            'bonus' => 'decimal:2',
            'advance_deduction' => 'decimal:2',
            'absence_days' => 'decimal:2',
            'absence_deduction' => 'decimal:2',
            'other_deductions' => 'decimal:2',
            'net_salary' => 'decimal:2',
            'is_paid' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (PayrollItem $item) {
            $item->net_salary = $item->base_salary + $item->allowances
                + $item->housing_allowance + $item->transport_allowance + $item->food_allowance
                + $item->overtime_amount + $item->bonus
                - $item->advance_deduction - $item->absence_deduction - $item->other_deductions;
        });

        static::saved(fn(PayrollItem $item) => $item->payrollRun?->recalculateTotal());
        static::deleted(fn(PayrollItem $item) => $item->payrollRun?->recalculateTotal());
    }

    public function payrollRun()
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function paymentVoucher()
    {
        return $this->belongsTo(PaymentVoucher::class);
    }
}
