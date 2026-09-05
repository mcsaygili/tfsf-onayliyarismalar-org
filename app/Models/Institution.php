<?php

namespace App\Models;

use App\Models\Concerns\HasSecurityStamp;
use Database\Factories\InstitutionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Yarışma düzenleyen kurum/dernek/üniversite — kimlik/oturum açan bir
 * varlık değil, InstitutionStaff'ın (guard: institution) bağlı olduğu
 * organizasyon.
 */
#[Fillable(['name', 'email', 'phone', 'website', 'address', 'status', 'approved_at', 'institution_type_id'])]
class Institution extends Model
{
    /** @use HasFactory<InstitutionFactory> */
    use HasFactory, HasSecurityStamp, HasUuids;

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

    public function competitions(): HasMany
    {
        return $this->hasMany(Competition::class);
    }

    public function institutionType(): BelongsTo
    {
        return $this->belongsTo(InstitutionType::class);
    }

    /**
     * Bu fazda kayıt anında otomatik atanıyor (bkz. RegisteredController).
     * İleride TFSF admin onayı gerektiren gerçek bir işleme dönüşecek.
     */
    public function isApproved(): bool
    {
        return $this->approved_at !== null;
    }

    /**
     * Yarışma başvurusu açabilmek için gereken asgari kurum bilgileri.
     * Profil formundaki zorunlu alanlarla aynı tutulmalıdır.
     */
    public function hasCompleteProfile(): bool
    {
        return filled($this->name)
            && filled($this->email)
            && filled($this->phone);
    }
}
