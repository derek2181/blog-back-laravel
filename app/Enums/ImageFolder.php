<?php

namespace App\Enums;

enum ImageFolder: string
{
    case albums = 'albums';
    case itzy = 'itzy';

    public static function values(): array
    {
        return array_map(static fn (self $case) => $case->value, self::cases());
    }
}
