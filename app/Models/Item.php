<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    public static $snakeAttributes = false;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'description',
        'image',
        'category',
        'type',
        'releaseYear',
        'postedAt',
        'updatedAt',
        'readTime',
        'video',
        'tagsJson',
        'galleryJson',
    ];

    protected $casts = [
        'postedAt' => 'datetime',
        'updatedAt' => 'datetime',
    ];

    public function itemImages(): HasMany
    {
        return $this->hasMany(ItemImage::class, 'itemId');
    }
}
