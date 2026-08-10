<?php

namespace App\Http\Requests\Uye;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EquipmentStoreRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'equipment_model_id' => [
                'required', 'uuid',
                Rule::exists('equipment_models', 'id')->where('status', true),
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
