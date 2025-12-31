<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ImageAsset extends Model
{
    public static $snakeAttributes = false;

    public $timestamps = false;

    protected $fillable = [
        'path',
        'folderKey',
        'originalName',
        'mimeType',
        'sizeBytes',
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

    public function itemImages(): HasMany
    {
        return $this->hasMany(ItemImage::class, 'imageAssetId');
    }
}
