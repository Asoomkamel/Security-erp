<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentVoucher extends Model
{
    use HasFactory, \App\Models\Concerns\Auditable;

    protected $fillable = [
        'cash_account_id', 'supplier_id', 'purchase_invoice_id', 'employee_id',
        'voucher_number', 'voucher_date', 'amount', 'payment_method', 'purpose',
        'cost_category', 'paid_to', 'reference_number', 'status', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'voucher_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function cashAccount()
    {
        return $this->belongsTo(CashAccount::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseInvoice()
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * تفعيل أثر السند: تنقيص رصيد الصندوق + زيادة المبلغ المسدد بفاتورة الشراء المرتبطة (إن وُجدت)
     */
    public function applyEffects(): void
    {
        $this->cashAccount->decreaseBalance((float) $this->amount);

        if ($this->purchaseInvoice) {
            $this->purchaseInvoice->increment('paid_amount', $this->amount);
            $this->purchaseInvoice->refreshPaymentStatus();
        }
    }

    /**
     * عكس أثر السند عند الإلغاء: إعادة المبلغ لرصيد الصندوق + تنقيص المبلغ المسدد بفاتورة الشراء
     */
    public function reverseEffects(): void
    {
        $this->cashAccount->increaseBalance((float) $this->amount);

        if ($this->purchaseInvoice) {
            $this->purchaseInvoice->decrement('paid_amount', $this->amount);
            $this->purchaseInvoice->refreshPaymentStatus();
        }
    }

    public function cancel(): void
    {
        if ($this->status === 'cancelled') {
            return;
        }

        $this->reverseEffects();
        $this->update(['status' => 'cancelled']);
    }

    public function scopeExpenses($query)
    {
        return $query->where('purpose', 'expense');
    }
}
