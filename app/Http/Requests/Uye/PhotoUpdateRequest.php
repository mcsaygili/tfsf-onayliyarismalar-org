<?php

namespace App\Http\Requests\Uye;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PhotoUpdateRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
            'taken_at' => ['required', 'date', 'before_or_equal:today'],
            'tags' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:2000'],
            'photo_category_id' => ['nullable', 'uuid', 'exists:photo_categories,id'],
        ];
    }
}
