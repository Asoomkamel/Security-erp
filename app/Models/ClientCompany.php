<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientCompany extends Model
{
    use HasFactory, SoftDeletes, BranchScope;

    protected $fillable = [
        'branch_id', 'name', 'commercial_register', 'tax_number', 'contact_person',
        'phone', 'email', 'address', 'is_active', 'credit_limit', 'payment_terms_days',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'credit_limit' => 'decimal:2',
        ];
    }

    public function sites()
    {
        return $this->hasMany(Site::class);
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function activeContracts()
    {
        return $this->contracts()->where('status', 'active');
    }

    public function salesInvoices()
    {
        return $this->hasMany(SalesInvoice::class);
    }

    public function unpaidInvoicesTotal(): float
    {
        return (float) $this->salesInvoices()->unpaid()->sum('total_amount');
    }

    /**
     * تحقق فعلي: هل يمكن إصدار فاتورة جديدة بهذا المبلغ دون تجاوز الحد الائتماني؟
     * (تفعيل حقيقي للحقل، بخلاف تخزينه فقط كمعلومة كما في المشروع المرجعي)
     */
    public function hasAvailableCredit(float $newInvoiceAmount): bool
    {
        if ($this->credit_limit <= 0) {
            return true; // 0 = بدون حد ائتماني مفروض
        }

        return ($this->unpaidInvoicesTotal() + $newInvoiceAmount) <= $this->credit_limit;
    }
}
