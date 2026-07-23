<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentOrder extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'order_number', 'supplier_id', 'purchase_invoice_id', 'amount', 'due_date',
        'description', 'status', 'approved_by', 'approved_at', 'payment_voucher_id', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'due_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function purchaseInvoice() { return $this->belongsTo(PurchaseInvoice::class); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
    public function paymentVoucher() { return $this->belongsTo(PaymentVoucher::class); }

    public function approve(int $userId): void
    {
        $this->update(['status' => 'approved', 'approved_by' => $userId, 'approved_at' => now()]);
    }

    public function reject(int $userId): void
    {
        $this->update(['status' => 'rejected', 'approved_by' => $userId, 'approved_at' => now()]);
    }
}
