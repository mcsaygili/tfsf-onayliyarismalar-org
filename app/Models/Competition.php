<?php

namespace App\Models;

use App\Enums\CompetitionAudience;
use App\Enums\CompetitionInfrastructureProvider;
use App\Enums\CompetitionStatus;
use App\Models\Concerns\HasTranslations;
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
#[Fillable(['institution_id', 'institution_staff_id', 'audience', 'infrastructure_provider', 'competition_type_id', 'country_id', 'city_id', 'participant_approval_process_id', 'partners'])]
class Competition extends Model
{
    use HasFactory, HasTranslations, HasUuids;

    /** @var array<int, string> */
    protected array $translatedAttributes = ['name', 'subject', 'purpose'];

    protected function casts(): array
    {
        return [
            'audience' => CompetitionAudience::class,
            'infrastructure_provider' => CompetitionInfrastructureProvider::class,
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

    public function competitionType(): BelongsTo
    {
        return $this->belongsTo(CompetitionType::class)->withTrashed();
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class)->withTrashed();
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class)->withTrashed();
    }

    public function participantApprovalProcess(): BelongsTo
    {
        return $this->belongsTo(ParticipantApprovalProcess::class)->withTrashed();
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(EysUser::class, 'reviewed_by');
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(CompetitionStatusLog::class)->latest();
    }

    public function categories(): HasMany
    {
        return $this->hasMany(CompetitionCategory::class)->orderBy('sort_order');
    }

    public function isEditable(): bool
    {
        return $this->status->isEditableByInstitution();
    }

    public function canSubmit(): bool
    {
        return CompetitionStepRegistry::canSubmit($this);
    }

    public function requiresEnglishContent(): bool
    {
        return $this->audience === CompetitionAudience::International;
    }
}
