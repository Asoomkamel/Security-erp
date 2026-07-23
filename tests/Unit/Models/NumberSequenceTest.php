<?php

use App\Models\NumberSequence;

it('returns sequential numbers for the same type', function () {
    $first = NumberSequence::next('TEST_TYPE', 'TST');
    $second = NumberSequence::next('TEST_TYPE', 'TST');
    $third = NumberSequence::next('TEST_TYPE', 'TST');

    expect($first)->toBe('TST-0001')
        ->and($second)->toBe('TST-0002')
        ->and($third)->toBe('TST-0003');
});

it('keeps separate counters for different types', function () {
    NumberSequence::next('TYPE_A', 'AAA');
    NumberSequence::next('TYPE_A', 'AAA');
    $b = NumberSequence::next('TYPE_B', 'BBB');

    expect($b)->toBe('BBB-0001');
});

it('throws when the prefix does not match the stored prefix for that type', function () {
    NumberSequence::next('TEST_TYPE', 'TST');

    expect(fn() => NumberSequence::next('TEST_TYPE', 'OTHER'))
        ->toThrow(RuntimeException::class);
});
