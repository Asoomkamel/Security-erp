<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Contract extends Model
{
    use HasFactory, SoftDeletes, \App\Models\Concerns\Auditable, BranchScope;

    protected $fillable = [
        'client_company_id', 'contract_number', 'contract_type', 'billing_cycle',
        'start_date', 'end_date', 'total_value', 'recurring_amount', 'auto_renew',
        'status', 'last_invoiced_at', 'next_invoice_due_at', 'payment_terms',
        'notes', 'attachment_path',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'last_invoiced_at' => 'date',
            'next_invoice_due_at' => 'date',
            'total_value' => 'decimal:2',
            'recurring_amount' => 'decimal:2',
            'auto_renew' => 'boolean',
        ];
    }

    public function clientCompany()
    {
        return $this->belongsTo(ClientCompany::class);
    }

    public function contractSites()
    {
        return $this->hasMany(ContractSite::class);
    }

    public function sites()
    {
        return $this->belongsToMany(Site::class, 'contract_sites')
            ->withPivot('guards_count', 'unit_price', 'site_total')
            ->withTimestamps();
    }

    // ملاحظة: علاقة الفواتير sales_invoices ستُضاف في Phase 3
    public function invoices()
    {
        return $this->hasMany(SalesInvoice::class);
    }

    /** إجمالي قيمة العقد المحسوبة من كل المواقع المرتبطة به */
    public function calculatedTotal(): float
    {
        return (float) $this->contractSites()->sum('site_total');
    }

    /** إجمالي عدد الحراس المطلوبين لكل مواقع هذا العقد */
    public function totalGuardsRequired(): int
    {
        return (int) $this->contractSites()->sum('guards_count');
    }

    /** هل العقد على وشك الانتهاء خلال (n) يوم القادمة */
    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->end_date
            && $this->end_date->isFuture()
            && now()->diffInDays($this->end_date) <= $days;
    }

    public function isExpired(): bool
    {
        return $this->end_date && $this->end_date->isPast();
    }

    /** هل هذا العقد مستحق لإصدار فاتورة الآن (بناءً على دورة الفوترة) */
    public function isDueForInvoicing(): bool
    {
        if ($this->status !== 'active' || $this->billing_cycle === 'one_time') {
            return $this->billing_cycle === 'one_time' && !$this->last_invoiced_at;
        }

        return $this->next_invoice_due_at && $this->next_invoice_due_at->isPast();
    }

    /** حساب موعد الفاتورة القادمة بناءً على دورة الفوترة */
    public function calculateNextInvoiceDate(?Carbon $from = null): ?Carbon
    {
        $from = $from ?? $this->last_invoiced_at ?? $this->start_date;

        return match ($this->billing_cycle) {
            'monthly' => $from->copy()->addMonth(),
            'quarterly' => $from->copy()->addMonths(3),
            'annual' => $from->copy()->addYear(),
            'one_time' => null,
            default => null,
        };
    }

    /** تحديث حالة العقد تلقائيًا حسب التاريخ (تُستخدم في أمر Artisan مجدول) */
    public function refreshStatus(): void
    {
        if ($this->status === 'cancelled') {
            return;
        }

        if ($this->isExpired()) {
            $this->update(['status' => 'expired']);
        } elseif ($this->status === 'draft' && $this->start_date->isPast()) {
            $this->update(['status' => 'active']);
        }
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('status', 'active')
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [now(), now()->addDays($days)]);
    }

    public function scopeDueForInvoicing($query)
    {
        return $query->where('status', 'active')
            ->where('billing_cycle', '!=', 'one_time')
            ->where(function ($q) {
                $q->whereNull('next_invoice_due_at')
                  ->orWhere('next_invoice_due_at', '<=', now());
            });
    }
}
