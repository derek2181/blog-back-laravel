<?php

namespace App\Enums;

enum ItemType: string
{
    case ALBUM = 'ALBUM';
    case HOBBY = 'HOBBY';
    case BLOG = 'BLOG';

    public static function values(): array
    {
        return array_map(static fn (self $case) => $case->value, self::cases());
    }
}
