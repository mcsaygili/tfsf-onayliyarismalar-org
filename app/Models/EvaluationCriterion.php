<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['code', 'default_min_score', 'default_max_score', 'default_weight', 'sort_order', 'status', 'is_system', 'version'])]
class EvaluationCriterion extends Model
{
    use HasTranslations, HasUuids, SoftDeletes;

    protected array $translatedAttributes = ['name', 'description'];

    protected function casts(): array
    {
        return [
            'default_min_score' => 'integer',
            'default_max_score' => 'integer',
            'default_weight' => 'decimal:2',
            'sort_order' => 'integer',
            'status' => 'boolean',
            'is_system' => 'boolean',
            'version' => 'integer',
        ];
    }

    public function categoryCriteria(): HasMany
    {
        return $this->hasMany(CompetitionCategoryEvaluationCriterion::class);
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
