<?php

use App\Enums\UserRole;
use App\Models\ClientCompany;
use App\Models\Site;

it('allows an admin to create a contract with priced sites', function () {
    actingAsUser(UserRole::Admin);

    $client = ClientCompany::factory()->create();
    $site = Site::factory()->create(['client_company_id' => $client->id]);

    $response = $this->post(route('contracts.store'), [
        'client_company_id' => $client->id,
        'contract_number' => 'CNT-FEATURE-0001',
        'contract_type' => 'monthly',
        'billing_cycle' => 'monthly',
        'start_date' => now()->toDateString(),
        'status' => 'active',
        'sites' => [
            ['site_id' => $site->id, 'guards_count' => 3, 'unit_price' => 1500],
        ],
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('contracts', ['contract_number' => 'CNT-FEATURE-0001']);
    $this->assertDatabaseHas('contract_sites', ['site_id' => $site->id, 'guards_count' => 3, 'unit_price' => 1500]);
});

it('returns a validation error when the sites array is empty', function () {
    actingAsUser(UserRole::Admin);

    $client = ClientCompany::factory()->create();

    $response = $this->post(route('contracts.store'), [
        'client_company_id' => $client->id,
        'contract_number' => 'CNT-FEATURE-0002',
        'contract_type' => 'monthly',
        'billing_cycle' => 'monthly',
        'start_date' => now()->toDateString(),
        'status' => 'active',
        'sites' => [],
    ]);

    $response->assertSessionHasErrors(['sites']);
});
