<?php

use App\Models\Setting;
use App\Services\InvoiceNumberGenerator;
use App\Support\SettingKeys;

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

test('a custom prefix setting is honored', function () {
    Setting::set(SettingKeys::INVOICE_PREFIX, 'ACME');
    $generator = app(InvoiceNumberGenerator::class);

    expect($generator->generate(2026))->toBe('ACME-2026-000001');
});

test('continuous numbering never resets and carries no year segment', function () {
    Setting::set(SettingKeys::INVOICE_NUMBERING_RESET, SettingKeys::NUMBERING_RESET_CONTINUOUS);
    $generator = app(InvoiceNumberGenerator::class);

    expect($generator->generate(2026))->toBe('INV-000001')
        ->and($generator->generate(2027))->toBe('INV-000002')
        ->and($generator->generate(2026))->toBe('INV-000003');
});

test('switching from yearly to continuous starts a fresh, independent sequence', function () {
    $generator = app(InvoiceNumberGenerator::class);

    expect($generator->generate(2026))->toBe('INV-2026-000001')
        ->and($generator->generate(2026))->toBe('INV-2026-000002');

    Setting::set(SettingKeys::INVOICE_NUMBERING_RESET, SettingKeys::NUMBERING_RESET_CONTINUOUS);

    expect($generator->generate(2026))->toBe('INV-000001');
});

test('a long run of generated numbers are all unique', function () {
    $generator = app(InvoiceNumberGenerator::class);

    $numbers = collect(range(1, 25))->map(fn () => $generator->generate(2026));

    expect($numbers->unique())->toHaveCount(25);
});
