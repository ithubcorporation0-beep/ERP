<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case BANK_TRANSFER = 'BANK_TRANSFER';
    case CARD = 'CARD';
    case CASH = 'CASH';
    case CHECK = 'CHECK';
    case ONLINE = 'ONLINE';

    public function label(): string
    {
        return match ($this) {
            self::BANK_TRANSFER => 'Bank Transfer',
            self::CARD => 'Card',
            self::CASH => 'Cash',
            self::CHECK => 'Check',
            self::ONLINE => 'Online',
        };
    }
}
