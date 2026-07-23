<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;

trait BranchScope
{
    public static function bootBranchScope(): void
    {
        static::addGlobalScope('branch', function (Builder $builder) {
            if (app()->runningInConsole()) return;
            $user = auth()->user();
            if (!$user || $user->isAdmin()) return;
            if ($user->branch_id) {
                $builder->where('branch_id', $user->branch_id);
            }
        });
    }
}
