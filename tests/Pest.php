<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->in('Feature', 'Unit');

function actingAsUser(\App\Enums\UserRole $role = \App\Enums\UserRole::Admin): \App\Models\User
{
    $user = \App\Models\User::factory()->role($role)->create();
    test()->actingAs($user);
    return $user;
}
