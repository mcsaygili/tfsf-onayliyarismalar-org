<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['code', 'icon_key', 'requires_location', 'requires_approval_process', 'sort_order', 'status', 'is_system', 'version'])]
class CompetitionType extends Model
{
    use HasFactory, HasTranslations, HasUuids, SoftDeletes;

    /** @var array<int, string> */
    protected array $translatedAttributes = ['name', 'description'];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'requires_location' => 'boolean',
            'requires_approval_process' => 'boolean',
            'is_system' => 'boolean',
            'version' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function competitions(): HasMany
    {
        return $this->hasMany(Competition::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
