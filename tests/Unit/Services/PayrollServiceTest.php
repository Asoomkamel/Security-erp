<?php

use App\Models\CashAccount;
use App\Models\Employee;
use App\Models\PayrollItem;
use App\Services\PayrollService;

beforeEach(function () {
    $this->service = app(PayrollService::class);
});

it('throws when creating a duplicate payroll run for the same month and year', function () {
    $this->service->createRun(7, 2026);

    expect(fn() => $this->service->createRun(7, 2026))
        ->toThrow(RuntimeException::class);
});

it('creates payroll items only for active employees', function () {
    Employee::factory()->count(2)->create(['status' => 'active']);
    Employee::factory()->create(['status' => 'terminated']);

    $run = $this->service->createRun(7, 2026);

    expect($run->items()->count())->toBe(2);
});

it('computes net salary correctly from base salary and allowances', function () {
    Employee::factory()->withSalary(3500, 300, 0)->create(['status' => 'active']);

    $run = $this->service->createRun(7, 2026);
    $item = $run->items()->first();

    expect((float) $item->net_salary)->toBe(3800.0);
});

it('changes the run status to approved', function () {
    Employee::factory()->create(['status' => 'active']);
    $run = $this->service->createRun(7, 2026);

    $this->service->approve($run, 1);

    expect($run->fresh()->status)->toBe('approved');
});

it('throws when trying to pay an item from an unapproved run', function () {
    Employee::factory()->create(['status' => 'active']);
    $run = $this->service->createRun(7, 2026); // draft بدون اعتماد
    $item = $run->items()->first();
    $cashAccount = CashAccount::factory()->create();

    expect(fn() => $this->service->payItem($item, $cashAccount))
        ->toThrow(RuntimeException::class);
});
