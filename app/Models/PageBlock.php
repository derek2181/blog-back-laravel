<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageBlock extends Model
{
    public static $snakeAttributes = false;

    public const CREATED_AT = 'createdAt';
    public const UPDATED_AT = 'updatedAt';

    protected $fillable = [
        'pageId',
        'type',
        'order',
        'content',
        'imagePath',
        'metadata',
    ];

    protected $casts = [
        'content' => 'array',
        'metadata' => 'array',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'pageId');
    }
}
