<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Site extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_company_id', 'name', 'address', 'latitude', 'longitude', 'geofence_radius_meters',
        'site_manager_name', 'site_manager_phone', 'required_guards_count', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function hasGeofence(): bool
    {
        return !is_null($this->latitude) && !is_null($this->longitude);
    }

    public function clientCompany()
    {
        return $this->belongsTo(ClientCompany::class);
    }

    public function assignments()
    {
        return $this->hasMany(EmployeeSiteAssignment::class);
    }

    public function activeGuards()
    {
        return $this->assignments()->where('is_active', true)->with('employee');
    }

    // عدد الحراس الحاليين مقابل العدد المطلوب (لمعرفة النقص)
    public function guardsShortage(): int
    {
        $current = $this->activeGuards()->count();
        return max(0, $this->required_guards_count - $current);
    }

    public function contractSites()
    {
        return $this->hasMany(ContractSite::class);
    }

    public function contracts()
    {
        return $this->belongsToMany(Contract::class, 'contract_sites')
            ->withPivot('guards_count', 'unit_price', 'site_total')
            ->withTimestamps();
    }
}
