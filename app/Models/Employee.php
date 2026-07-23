<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes, BranchScope;

    protected $fillable = [
        'branch_id', 'user_id', 'employee_code', 'full_name', 'national_id', 'phone', 'phone_alt',
        'address', 'birth_date', 'hire_date', 'termination_date', 'employee_type',
        'job_title', 'status', 'base_salary', 'housing_allowance', 'transport_allowance', 'food_allowance',
        'photo_path', 'id_document_path',
        'id_expiry_date', 'notes',
        // مستفاد من مراجعة نسخة ثانية: بيانات هوية/تأمين/رخصة أدق
        'passport_number', 'passport_expiry', 'iqama_number', 'iqama_expiry',
        'gosi_number', 'security_license_number', 'security_license_expiry',
        'medical_insurance_number', 'bank_name', 'bank_account', 'iban',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'hire_date' => 'date',
            'termination_date' => 'date',
            'id_expiry_date' => 'date',
            'base_salary' => 'decimal:2',
            'housing_allowance' => 'decimal:2',
            'transport_allowance' => 'decimal:2',
            'food_allowance' => 'decimal:2',
            'passport_expiry' => 'date',
            'iqama_expiry' => 'date',
            'security_license_expiry' => 'date',
        ];
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function documents()
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    /** أقرب تاريخ انتهاء بين (الهوية/الإقامة، الجواز، رخصة الحراسة) لأغراض التنبيه الموحّد */
    public function nearestExpiryAlert(int $days = 30): ?array
    {
        $candidates = collect([
            ['label' => 'الهوية/الإقامة', 'date' => $this->id_expiry_date],
            ['label' => 'رقم الإقامة', 'date' => $this->iqama_expiry],
            ['label' => 'الجواز', 'date' => $this->passport_expiry],
            ['label' => 'رخصة الحراسة', 'date' => $this->security_license_expiry],
        ])->filter(fn($c) => $c['date'] && $c['date']->isFuture() && now()->diffInDays($c['date']) <= $days)
          ->sortBy('date');

        return $candidates->first();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function siteAssignments()
    {
        return $this->hasMany(EmployeeSiteAssignment::class);
    }

    // التعيين الحالي النشط للحارس (الموقع الذي يعمل به الآن)
    public function currentAssignment()
    {
        return $this->hasOne(EmployeeSiteAssignment::class)->where('is_active', true);
    }

    public function isGuard(): bool
    {
        return $this->employee_type === 'guard';
    }

    // تنبيه: هل ستنتهي هويته/إقامته خلال 30 يوم القادمة
    public function idExpiringSoon(): bool
    {
        return $this->id_expiry_date
            && $this->id_expiry_date->isFuture()
            && now()->diffInDays($this->id_expiry_date) <= 30;
    }

    public function advances()
    {
        return $this->hasMany(EmployeeAdvance::class);
    }

    public function activeAdvances()
    {
        return $this->advances()->active();
    }

    public function payrollItems()
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function totalActiveAdvancesBalance(): float
    {
        return (float) $this->activeAdvances()->sum('remaining_balance');
    }
}
