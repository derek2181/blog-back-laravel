<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ItemImage extends Model
{
    public static $snakeAttributes = false;

    public $timestamps = false;

    protected $fillable = [
        'itemId',
        'imageAssetId',
        'role',
        'order',
    ];

    protected $keyType = 'string';
    public $incrementing = false;

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (!$model->id) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'itemId');
    }

    public function imageAsset(): BelongsTo
    {
        return $this->belongsTo(ImageAsset::class, 'imageAssetId');
    }
}
