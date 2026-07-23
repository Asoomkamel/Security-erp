<?php

use App\Enums\UserRole;
use App\Models\ClientCompany;
use App\Models\Employee;
use App\Models\Site;

it('allows an authenticated supervisor to store an attendance record', function () {
    actingAsUser(UserRole::Supervisor);

    $employee = Employee::factory()->create();
    $client = ClientCompany::factory()->create();
    $site = Site::factory()->create(['client_company_id' => $client->id]);

    $response = $this->post(route('attendance.store'), [
        'employee_id' => $employee->id,
        'site_id' => $site->id,
        'date' => now()->toDateString(),
        'status' => 'present',
        'shift' => 'full_day',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('attendances', [
        'employee_id' => $employee->id,
        'site_id' => $site->id,
        'status' => 'present',
    ]);
});

it('returns validation errors for an empty attendance submission', function () {
    actingAsUser(UserRole::Supervisor);

    $response = $this->post(route('attendance.store'), []);

    $response->assertSessionHasErrors(['employee_id', 'date', 'status', 'shift']);
});
