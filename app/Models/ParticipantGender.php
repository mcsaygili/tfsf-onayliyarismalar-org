<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['code', 'sort_order', 'status'])]
class ParticipantGender extends Model
{
    use HasTranslations, HasUuids, SoftDeletes;

    protected array $translatedAttributes = ['name', 'description'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer', 'status' => 'boolean'];
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('code');
    }
}
