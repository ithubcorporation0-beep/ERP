<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case DRAFT = 'DRAFT';
    case SENT = 'SENT';
    case PARTIALLY_PAID = 'PARTIALLY_PAID';
    case PAID = 'PAID';
    case VOID = 'VOID';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::SENT => 'Sent',
            self::PARTIALLY_PAID => 'Partially Paid',
            self::PAID => 'Paid',
            self::VOID => 'Void',
        };
    }

    /**
     * Tailwind badge classes for this status.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::DRAFT => 'bg-gray-100 text-gray-700',
            self::SENT => 'bg-blue-100 text-blue-700',
            self::PARTIALLY_PAID => 'bg-yellow-100 text-yellow-700',
            self::PAID => 'bg-green-100 text-green-700',
            self::VOID => 'bg-red-100 text-red-700',
        };
    }
}
