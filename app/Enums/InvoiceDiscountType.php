<?php

namespace App\Enums;

enum InvoiceDiscountType: string
{
    case NONE = 'NONE';
    case PERCENT = 'PERCENT';
    case AMOUNT = 'AMOUNT';

    public function label(): string
    {
        return match ($this) {
            self::NONE => 'No discount',
            self::PERCENT => 'Percent',
            self::AMOUNT => 'Fixed amount',
        };
    }
}
