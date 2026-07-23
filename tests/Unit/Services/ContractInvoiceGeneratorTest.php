<?php

use App\Models\Contract;
use App\Models\Site;
use App\Services\ContractInvoiceGenerator;

beforeEach(function () {
    $this->generator = app(ContractInvoiceGenerator::class);
});

it('generates an auto-contract sales invoice with a positive total when the contract has sites', function () {
    $contract = Contract::factory()->create();
    $site = Site::factory()->create(['client_company_id' => $contract->client_company_id]);

    $contract->contractSites()->create([
        'site_id' => $site->id,
        'guards_count' => 3,
        'unit_price' => 1500,
        'site_total' => 4500,
    ]);

    $invoice = $this->generator->generateForContract($contract->fresh());

    expect($invoice)->not->toBeNull()
        ->and($invoice->source)->toBe('auto_contract')
        ->and((float) $invoice->total_amount)->toBeGreaterThan(0);
});

it('returns null when the contract has no sites', function () {
    $contract = Contract::factory()->create();

    $invoice = $this->generator->generateForContract($contract);

    expect($invoice)->toBeNull();
});
