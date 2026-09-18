<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class InvoiceNumberGenerator
{
    /**
     * Generate the next sequential invoice number for the given year, in
     * the form "INV-YYYY-######". The sequence is stored per year in the
     * settings table (key "invoice_sequence_{year}") and incremented
     * atomically so concurrent requests never collide.
     */
    public function generate(?int $year = null): string
    {
        $year ??= (int) now()->year;
        $key = "invoice_sequence_{$year}";

        $next = DB::transaction(function () use ($key) {
            Setting::firstOrCreate(['key' => $key], ['value' => '0']);

            $setting = Setting::where('key', $key)->lockForUpdate()->first();

            $next = ((int) $setting->value) + 1;
            $setting->update(['value' => (string) $next]);

            return $next;
        });

        return sprintf('INV-%d-%06d', $year, $next);
    }
}
