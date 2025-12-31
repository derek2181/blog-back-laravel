<?php

namespace App\Http\Requests;

use App\Enums\PageBlockType;
use Illuminate\Validation\Rule;

class UpsertPageRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string'],
            'blocks' => ['required', 'array'],
            'blocks.*.type' => ['required', 'string', Rule::in(PageBlockType::values())],
            'blocks.*.order' => ['required', 'integer'],
            'blocks.*.content' => ['nullable', 'array'],
            'blocks.*.imagePath' => ['nullable', 'string'],
            'blocks.*.metadata' => ['nullable', 'array'],
        ];
    }
}
