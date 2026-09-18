<?php

use App\Services\InvoiceNumberGenerator;

test('generates sequential numbers within a year in the INV-YYYY-###### form', function () {
    $generator = app(InvoiceNumberGenerator::class);

    expect($generator->generate(2026))->toBe('INV-2026-000001')
        ->and($generator->generate(2026))->toBe('INV-2026-000002')
        ->and($generator->generate(2026))->toBe('INV-2026-000003');
});

test('each year gets its own independent sequence', function () {
    $generator = app(InvoiceNumberGenerator::class);

    expect($generator->generate(2026))->toBe('INV-2026-000001')
        ->and($generator->generate(2027))->toBe('INV-2027-000001')
        ->and($generator->generate(2026))->toBe('INV-2026-000002');
});

test('defaults to the current year when none is given', function () {
    $generator = app(InvoiceNumberGenerator::class);

    expect($generator->generate())->toBe('INV-'.now()->year.'-000001');
});
