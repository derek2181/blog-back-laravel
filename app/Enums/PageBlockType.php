<?php

namespace App\Enums;

enum PageBlockType: string
{
    case HERO = 'HERO';
    case TEXT = 'TEXT';
    case TAGS = 'TAGS';
    case FACTS = 'FACTS';
    case PASSIONS = 'PASSIONS';
    case GALLERY = 'GALLERY';
    case CTA = 'CTA';
    case IMAGE = 'IMAGE';
    case LIST = 'LIST';
    case GROUP = 'GROUP';
    case SHOWCASE = 'SHOWCASE';
    case SOCIAL = 'SOCIAL';
    case POSTS = 'POSTS';
    case NEWSLETTER = 'NEWSLETTER';

    public static function values(): array
    {
        return array_map(static fn (self $case) => $case->value, self::cases());
    }
}
