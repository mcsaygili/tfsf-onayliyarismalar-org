<?php

namespace App\Http\Requests\Uye;

use App\Models\PortfolioSetting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PhotoUploadRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'title' => ['required', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
            'taken_at' => ['required', 'date', 'before_or_equal:today'],
            'tags' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:2000'],
            'photo_category_id' => ['nullable', 'uuid', 'exists:photo_categories,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->user()->canUploadMorePhotos()) {
                $validator->errors()->add('photo', __('uye.portfolio.limit_reached', ['max' => PortfolioSetting::current()->max_photos_per_user]));
            }
        });
    }
}
