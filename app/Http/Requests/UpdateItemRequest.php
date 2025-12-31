<?php

namespace App\Http\Requests;

use App\Enums\ItemType;
use Illuminate\Validation\Rule;

class UpdateItemRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string'],
            'description' => ['sometimes', 'string'],
            'image' => ['sometimes', 'string'],
            'category' => ['sometimes', 'string'],
            'type' => ['sometimes', 'string', Rule::in(ItemType::values())],
            'releaseYear' => ['nullable', 'integer'],
            'postedAt' => ['nullable', 'date'],
            'updatedAt' => ['nullable', 'date'],
            'readTime' => ['nullable', 'string'],
            'video' => ['nullable', 'string'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string'],
            'gallery' => ['nullable', 'array'],
            'gallery.*' => ['string'],
        ];
    }
}
