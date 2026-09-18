<?php

namespace App\Enums;

enum ExpenseCategory: string
{
    case SOFTWARE = 'SOFTWARE';
    case TRAVEL = 'TRAVEL';
    case SUPPLIES = 'SUPPLIES';
    case SALARY = 'SALARY';
    case OTHER = 'OTHER';

    public function label(): string
    {
        return match ($this) {
            self::SOFTWARE => 'Software',
            self::TRAVEL => 'Travel',
            self::SUPPLIES => 'Supplies',
            self::SALARY => 'Salary',
            self::OTHER => 'Other',
        };
    }
}
