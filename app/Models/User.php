<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Scopes\BranchScope;

class User extends Authenticatable
{
    use HasFactory, Notifiable, BranchScope;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'is_active', 'branch_id',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function hasRole(string|array $roles): bool
    {
        $roles = is_array($roles) ? $roles : [$roles];
        return in_array($this->role, $roles);
    }

    /**
     * تجاوز مخصص للنطاق العام للفرع: يسمح للمستخدم دائمًا برؤية سجله الشخصي
     * حتى لو كان بفرع مختلف (مثال: قائمة "كل المستخدمين" تعرض له نفسه ضمن فرعه + سجله)
     */
    public static function bootBranchScope(): void
    {
        static::addGlobalScope('branch', function (Builder $builder) {
            if (app()->runningInConsole()) return;
            $user = auth()->user();
            if (!$user) return;
            if ($user->isAdmin()) return;
            if ($user->branch_id) {
                $builder->where('branch_id', $user->branch_id)
                        ->orWhere('id', $user->id);
            }
        });
    }
}
