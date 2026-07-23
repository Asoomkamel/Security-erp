<?php

use App\Models\ClientCompany;
use App\Models\SalesInvoice;

it('allows an invoice within the credit limit', function () {
    $client = ClientCompany::factory()->withCreditLimit(10000)->create();

    expect($client->hasAvailableCredit(5000))->toBeTrue();
});

it('rejects an invoice that would exceed the credit limit', function () {
    $client = ClientCompany::factory()->withCreditLimit(10000)->create();

    SalesInvoice::factory()->create([
        'client_company_id' => $client->id,
        'status' => 'unpaid',
        'subtotal' => 8000,
        'total_amount' => 8000,
    ]);

    expect($client->hasAvailableCredit(3000))->toBeFalse();
});

it('allows any amount when the credit limit is zero (unlimited)', function () {
    $client = ClientCompany::factory()->withCreditLimit(0)->create();

    expect($client->hasAvailableCredit(999999))->toBeTrue();
});
