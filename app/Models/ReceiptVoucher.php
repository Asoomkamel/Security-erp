<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceiptVoucher extends Model
{
    use HasFactory, \App\Models\Concerns\Auditable;

    protected $fillable = [
        'cash_account_id', 'client_company_id', 'sales_invoice_id', 'voucher_number',
        'voucher_date', 'amount', 'payment_method', 'received_from', 'reference_number',
        'status', 'notes', 'created_by',
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

    public function clientCompany()
    {
        return $this->belongsTo(ClientCompany::class);
    }

    public function salesInvoice()
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    /**
     * تفعيل أثر السند: زيادة رصيد الصندوق + زيادة المبلغ المسدد بالفاتورة المرتبطة (إن وُجدت)
     * تُستدعى مرة واحدة فقط عند إنشاء السند
     */
    public function applyEffects(): void
    {
        $this->cashAccount->increaseBalance((float) $this->amount);

        if ($this->salesInvoice) {
            $this->salesInvoice->increment('paid_amount', $this->amount);
            $this->salesInvoice->refreshPaymentStatus();
        }
    }

    /**
     * عكس أثر السند عند الإلغاء: تنقيص رصيد الصندوق + تنقيص المبلغ المسدد بالفاتورة
     */
    public function reverseEffects(): void
    {
        $this->cashAccount->decreaseBalance((float) $this->amount);

        if ($this->salesInvoice) {
            $this->salesInvoice->decrement('paid_amount', $this->amount);
            $this->salesInvoice->refreshPaymentStatus();
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
}
