<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id', 'action', 'auditable_type', 'auditable_id', 'old_data', 'new_data', 'ip_address',
    ];

    protected function casts(): array
    {
        return ['old_data' => 'array', 'new_data' => 'array'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
