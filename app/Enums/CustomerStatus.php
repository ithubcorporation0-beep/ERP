<?php

namespace App\Enums;

enum CustomerStatus: string
{
    case ACTIVE = 'ACTIVE';
    case INACTIVE = 'INACTIVE';
    case PROSPECT = 'PROSPECT';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::PROSPECT => 'Prospect',
        };
    }
}
