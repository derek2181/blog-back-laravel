<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Page extends Model
{
    public static $snakeAttributes = false;

    public const CREATED_AT = 'createdAt';
    public const UPDATED_AT = 'updatedAt';

    protected $fillable = [
        'key',
        'title',
    ];

    public function blocks(): HasMany
    {
        return $this->hasMany(PageBlock::class, 'pageId');
    }
}
