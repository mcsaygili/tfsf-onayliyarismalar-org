<?php

namespace App\Models;

use App\Enums\CompetitionStatus;
use App\Support\CompetitionWizard\CompetitionStepRegistry;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Bir kurumun yarışma başvurusu — taslaktan yayına kadar tek kayıt
 * (bkz. proje planı "Kurum Paneli — Yarışma Ekleme Sihirbazı").
 *
 * `status`/`current_step`/`reviewed_*`/`published_at`/`latest_review_message`
 * bilinçli olarak Fillable DIŞINDA — bunlar yalnızca controller mantığıyla
 * (CompetitionController::submit / CompetitionReviewController) değişmeli.
 */
#[Fillable(['institution_id', 'institution_staff_id', 'name', 'partners', 'subject', 'purpose'])]
class Competition extends Model
{
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'status' => CompetitionStatus::class,
            'current_step' => 'integer',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function institutionStaff(): BelongsTo
    {
        return $this->belongsTo(InstitutionStaff::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(EysUser::class, 'reviewed_by');
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(CompetitionStatusLog::class)->latest();
    }

    public function isEditable(): bool
    {
        return $this->status->isEditableByInstitution();
    }

    public function canSubmit(): bool
    {
        return CompetitionStepRegistry::canSubmit($this);
    }
}
