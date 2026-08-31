<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['key', 'name', 'description', 'variables', 'is_active'])]
class NotificationTemplate extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return ['variables' => 'array', 'is_active' => 'boolean'];
    }

    public function translations(): HasMany
    {
        return $this->hasMany(NotificationTemplateTranslation::class);
    }

    public function translation(string $locale): ?NotificationTemplateTranslation
    {
        $this->loadMissing('translations');

        return $this->translations->firstWhere('locale', $locale)
            ?? $this->translations->firstWhere('locale', config('app.fallback_locale', 'tr'))
            ?? $this->translations->first();
    }
}
