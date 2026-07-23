<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseInvoice extends Model
{
    use HasFactory, SoftDeletes, \App\Models\Concerns\Auditable;

    protected $fillable = [
        'supplier_id', 'invoice_number', 'invoice_date', 'due_date',
        'subtotal', 'tax_rate', 'tax_amount', 'total_amount', 'paid_amount',
        'status', 'category', 'attachment_path', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
        ];
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseInvoiceItem::class);
    }

    // ملاحظة: علاقة سندات الصرف المرتبطة ستُضاف في Phase 4
    public function paymentVouchers()
    {
        return $this->hasMany(PaymentVoucher::class);
    }

    public function recalculateTotals(): void
    {
        $subtotal = $this->items()->sum('total');
        $taxAmount = round($subtotal * ($this->tax_rate / 100), 2);

        $this->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $subtotal + $taxAmount,
        ]);

        $this->refreshPaymentStatus();
    }

    public function refreshPaymentStatus(): void
    {
        if ($this->status === 'cancelled') {
            return;
        }

        if ($this->paid_amount <= 0) {
            $status = 'unpaid';
        } elseif ($this->paid_amount >= $this->total_amount) {
            $status = 'paid';
        } else {
            $status = 'partially_paid';
        }

        if ($status !== $this->status) {
            $this->update(['status' => $status]);
        }
    }

    public function remainingAmount(): float
    {
        return max(0, (float) $this->total_amount - (float) $this->paid_amount);
    }

    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', ['unpaid', 'partially_paid']);
    }
}
