<?php

namespace App\Support;

/**
 * The known keys in the settings table (App\Models\Setting), and the
 * fixed option sets for the two settings that aren't free text.
 */
class SettingKeys
{
    public const COMPANY_NAME = 'company_name';

    /**
     * Not a `value` row — a media attachment (see Setting::registerMediaCollections()).
     */
    public const COMPANY_LOGO = 'company_logo';

    public const INVOICE_PREFIX = 'invoice_prefix';

    public const INVOICE_NUMBERING_RESET = 'invoice_numbering_reset';

    public const DEFAULT_CURRENCY = 'default_currency';

    public const DEFAULT_TAX_BEHAVIOR = 'default_tax_behavior';

    public const NUMBERING_RESET_YEARLY = 'yearly';

    public const NUMBERING_RESET_CONTINUOUS = 'continuous';

    public const NUMBERING_RESET_OPTIONS = [
        self::NUMBERING_RESET_YEARLY,
        self::NUMBERING_RESET_CONTINUOUS,
    ];

    public const TAX_BEHAVIOR_PER_LINE = 'PER_LINE';

    public const TAX_BEHAVIOR_INCLUSIVE = 'INCLUSIVE';

    /**
     * Only PER_LINE is actually implemented (InvoiceCalculationService
     * already computes tax per line item) — INCLUSIVE is captured as a
     * setting for now but doesn't change calculation behavior yet.
     */
    public const TAX_BEHAVIOR_OPTIONS = [
        self::TAX_BEHAVIOR_PER_LINE,
        self::TAX_BEHAVIOR_INCLUSIVE,
    ];
}
