<?php

namespace App\Models;

use Database\Factories\InstitutionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Yarışma düzenleyen kurum/dernek/üniversite — kimlik/oturum açan bir
 * varlık değil, InstitutionStaff'ın (guard: institution) bağlı olduğu
 * organizasyon.
 */
#[Fillable(['name', 'email', 'phone', 'website', 'address', 'status', 'approved_at'])]
class Institution extends Model
{
    /** @use HasFactory<InstitutionFactory> */
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    public function staff(): HasMany
    {
        return $this->hasMany(InstitutionStaff::class);
    }

    /**
     * Bu fazda kayıt anında otomatik atanıyor (bkz. RegisteredController).
     * İleride TFSF admin onayı gerektiren gerçek bir işleme dönüşecek.
     */
    public function isApproved(): bool
    {
        return $this->approved_at !== null;
    }
}
