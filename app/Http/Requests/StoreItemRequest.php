<?php

namespace App\Http\Requests;

use App\Enums\ItemType;
use Illuminate\Validation\Rule;

class StoreItemRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'description' => ['required', 'string'],
            'image' => ['required', 'string'],
            'category' => ['required', 'string'],
            'type' => ['required', 'string', Rule::in(ItemType::values())],
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
