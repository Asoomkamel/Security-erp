<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesInvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_invoice_id', 'site_id', 'description', 'quantity', 'unit_price', 'total',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (SalesInvoiceItem $item) {
            $item->total = $item->quantity * $item->unit_price;
        });

        // إعادة حساب مجاميع الفاتورة تلقائيًا عند إضافة/تعديل/حذف أي بند
        static::saved(fn(SalesInvoiceItem $item) => $item->invoice?->recalculateTotals());
        static::deleted(fn(SalesInvoiceItem $item) => $item->invoice?->recalculateTotals());
    }

    public function invoice()
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }
}
