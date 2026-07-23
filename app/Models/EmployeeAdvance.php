<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeAdvance extends Model
{
    use HasFactory, \App\Models\Concerns\Auditable;

    protected $fillable = [
        'employee_id', 'amount', 'given_date', 'monthly_deduction',
        'remaining_balance', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'given_date' => 'date',
            'amount' => 'decimal:2',
            'monthly_deduction' => 'decimal:2',
            'remaining_balance' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (EmployeeAdvance $advance) {
            if (is_null($advance->remaining_balance)) {
                $advance->remaining_balance = $advance->amount;
            }
        });
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * تحديد قيمة القسط الذي سيُخصم فعليًا هذا الشهر
     * (لا يتجاوز المتبقي، حتى لو كان القسط الشهري المحدد أكبر منه في آخر دفعة)
     */
    public function nextInstallmentAmount(): float
    {
        return (float) min($this->monthly_deduction, $this->remaining_balance);
    }

    /** خصم قسط من السلفة، وتحديث حالتها لـ settled إذا اكتمل السداد */
    public function applyInstallment(float $amount): void
    {
        $newBalance = max(0, $this->remaining_balance - $amount);

        $this->update([
            'remaining_balance' => $newBalance,
            'status' => $newBalance <= 0 ? 'settled' : 'active',
        ]);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')->where('remaining_balance', '>', 0);
    }
}
