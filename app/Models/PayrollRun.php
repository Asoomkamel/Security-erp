<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollRun extends Model
{
    use HasFactory, \App\Models\Concerns\Auditable;

    protected $fillable = [
        'month', 'year', 'total_net_amount', 'status', 'approved_by', 'approved_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'total_net_amount' => 'decimal:2',
        ];
    }

    public function items()
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function label(): string
    {
        return sprintf('رواتب شهر %02d-%d', $this->month, $this->year);
    }

    /** إعادة حساب إجمالي الرواتب الصافية من كل البنود */
    public function recalculateTotal(): void
    {
        $this->update(['total_net_amount' => $this->items()->sum('net_salary')]);
    }

    public function paidItemsCount(): int
    {
        return $this->items()->where('is_paid', true)->count();
    }

    public function isFullyPaid(): bool
    {
        return $this->items()->count() > 0 && $this->paidItemsCount() === $this->items()->count();
    }

    /** تُستدعى بعد صرف آخر بند لتحديث حالة التشغيل بالكامل إلى paid */
    public function refreshPaidStatus(): void
    {
        if ($this->isFullyPaid()) {
            $this->update(['status' => 'paid']);
        }
    }
}
