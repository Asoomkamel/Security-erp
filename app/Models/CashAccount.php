<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\BranchScope;

class CashAccount extends Model
{
    use HasFactory, BranchScope;

    protected $fillable = [
        'name', 'type', 'bank_name', 'account_number', 'iban',
        'opening_balance', 'current_balance', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'current_balance' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // عند إنشاء الحساب لأول مرة، الرصيد الحالي = الرصيد الافتتاحي
        static::creating(function (CashAccount $account) {
            if (is_null($account->current_balance)) {
                $account->current_balance = $account->opening_balance;
            }
        });
    }

    public function receiptVouchers()
    {
        return $this->hasMany(ReceiptVoucher::class);
    }

    public function paymentVouchers()
    {
        return $this->hasMany(PaymentVoucher::class);
    }

    /** إضافة مبلغ للرصيد (عند سند قبض) */
    public function increaseBalance(float $amount): void
    {
        $this->increment('current_balance', $amount);
    }

    /** خصم مبلغ من الرصيد (عند سند صرف) */
    public function decreaseBalance(float $amount): void
    {
        $this->decrement('current_balance', $amount);
    }
}
