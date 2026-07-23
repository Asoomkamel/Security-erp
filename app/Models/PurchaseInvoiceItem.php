<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseInvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_invoice_id', 'description', 'quantity', 'unit_price', 'total',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (PurchaseInvoiceItem $item) {
            $item->total = $item->quantity * $item->unit_price;
        });

        static::saved(fn(PurchaseInvoiceItem $item) => $item->invoice?->recalculateTotals());
        static::deleted(fn(PurchaseInvoiceItem $item) => $item->invoice?->recalculateTotals());
    }

    public function invoice()
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id');
    }
}
