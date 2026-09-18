<?php

namespace App\Enums;

enum InvoiceItemType: string
{
    case SERVICE = 'SERVICE';
    case PRODUCT = 'PRODUCT';
    case TEXT = 'TEXT';

    public function label(): string
    {
        return match ($this) {
            self::SERVICE => 'Service',
            self::PRODUCT => 'Product',
            self::TEXT => 'Custom line',
        };
    }
}
