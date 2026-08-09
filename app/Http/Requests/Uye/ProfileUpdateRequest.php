<?php

namespace App\Http\Requests\Uye;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'education_level_id' => ['nullable', 'uuid', 'exists:education_levels,id'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'unspecified'])],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }
}
