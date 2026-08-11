<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bakım Modu — her subdomain (Kurum/Temsilci/Jüri/Üye) için ayrı satır.
 * `module` sütunu Laravel guard adından bilinçli olarak ayrı tutuluyor
 * (Üye'nin guard'ı 'web' ama "module" olarak 'uye' okunması daha anlaşılır) —
 * eşleme GUARDS sabitinde.
 */
#[Fillable(['module', 'enabled', 'message', 'updated_by'])]
class MaintenanceMode extends Model
{
    use HasUuids;

    /** @var array<int, string> */
    public const MODULES = ['institution', 'temsilci', 'juri', 'uye'];

    /** @var array<string, string> */
    private const GUARDS = [
        'institution' => 'institution',
        'temsilci' => 'temsilci',
        'juri' => 'juri',
        'uye' => 'web',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
        ];
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(EysUser::class, 'updated_by');
    }

    public static function isEnabledFor(string $module): bool
    {
        return (bool) static::query()->where('module', $module)->value('enabled');
    }

    public static function guardFor(string $module): string
    {
        return self::GUARDS[$module] ?? 'web';
    }
}
