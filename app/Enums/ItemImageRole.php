<?php

namespace App\Enums;

enum ItemImageRole: string
{
    case PRIMARY = 'PRIMARY';
    case GALLERY = 'GALLERY';

    public static function values(): array
    {
        return array_map(static fn (self $case) => $case->value, self::cases());
    }
}
