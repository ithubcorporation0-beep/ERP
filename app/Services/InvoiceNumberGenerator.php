<?php

namespace App\Services;

use App\Models\Setting;
use App\Support\SettingKeys;
use Illuminate\Support\Facades\DB;

class InvoiceNumberGenerator
{
    /**
     * Generate the next invoice number, reading the prefix and numbering
     * reset rule from settings (App\Support\SettingKeys), defaulting to
     * "INV" and yearly if unset.
     *
     * Yearly (the default): the sequence resets to 1 every calendar year
     * and the number includes the year, e.g. "INV-2026-000001". The
     * sequence is stored per year (key "invoice_sequence_{year}").
     *
     * Continuous: the sequence never resets and the number has no year
     * segment, e.g. "INV-000001". Stored under a single fixed key
     * ("invoice_sequence_continuous") shared across all years.
     *
     * Either way, the counter is read-and-incremented inside a DB
     * transaction with a row lock, so concurrent requests never hand out
     * the same number — and invoices.number carries its own unique
     * constraint as a backstop.
     */
    public function generate(?int $year = null): string
    {
        $year ??= (int) now()->year;

        $prefix = Setting::get(SettingKeys::INVOICE_PREFIX, 'INV');
        $resetRule = Setting::get(SettingKeys::INVOICE_NUMBERING_RESET, SettingKeys::NUMBERING_RESET_YEARLY);
        $isYearly = $resetRule !== SettingKeys::NUMBERING_RESET_CONTINUOUS;

        $key = $isYearly ? "invoice_sequence_{$year}" : 'invoice_sequence_continuous';

        $next = DB::transaction(function () use ($key) {
            Setting::firstOrCreate(['key' => $key], ['value' => 0]);

            $setting = Setting::where('key', $key)->lockForUpdate()->first();

            $next = ((int) $setting->value) + 1;
            $setting->update(['value' => $next]);

            return $next;
        });

        return $isYearly
            ? sprintf('%s-%d-%06d', $prefix, $year, $next)
            : sprintf('%s-%06d', $prefix, $next);
    }
}
