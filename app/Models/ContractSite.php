<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractSite extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id', 'site_id', 'guards_count', 'unit_price', 'site_total',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'site_total' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        // حساب site_total تلقائيًا = عدد الحراس × السعر، قبل الحفظ مباشرة
        static::saving(function (ContractSite $contractSite) {
            $contractSite->site_total = $contractSite->guards_count * $contractSite->unit_price;
        });
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }
}
