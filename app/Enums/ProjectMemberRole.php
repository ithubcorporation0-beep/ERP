<?php

namespace App\Enums;

enum ProjectMemberRole: string
{
    case OWNER = 'OWNER';
    case CONTRIBUTOR = 'CONTRIBUTOR';
    case VIEWER = 'VIEWER';

    public function label(): string
    {
        return match ($this) {
            self::OWNER => 'Owner',
            self::CONTRIBUTOR => 'Contributor',
            self::VIEWER => 'Viewer',
        };
    }
}
