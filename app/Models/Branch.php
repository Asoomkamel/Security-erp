<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'city', 'phone', 'is_main', 'is_active'];

    protected function casts(): array
    {
        return ['is_main' => 'boolean', 'is_active' => 'boolean'];
    }

    public function users() { return $this->hasMany(User::class); }
    public function employees() { return $this->hasMany(Employee::class); }
    public function clientCompanies() { return $this->hasMany(ClientCompany::class); }
    public function contracts() { return $this->hasMany(Contract::class); }
    public function cashAccounts() { return $this->hasMany(CashAccount::class); }
    public function suppliers() { return $this->hasMany(Supplier::class); }
}
