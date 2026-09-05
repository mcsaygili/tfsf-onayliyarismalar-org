<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['competition_id', 'sort_order', 'max_photos_per_participant', 'photo_rules', 'photos_grouped', 'photo_story_required', 'category_story_required', 'photo_order_required', 'age_eligibility_rule_id', 'member_group_match_mode', 'birth_date_restricted', 'birth_date_from', 'birth_date_to'])]
class CompetitionCategory extends Model
{
    use HasTranslations, HasUuids, SoftDeletes;

    protected array $translatedAttributes = ['name'];

    protected function casts(): array
    {
        return ['photos_grouped' => 'boolean', 'photo_story_required' => 'boolean', 'category_story_required' => 'boolean', 'photo_order_required' => 'boolean', 'photo_rules' => 'array', 'sort_order' => 'integer', 'max_photos_per_participant' => 'integer', 'birth_date_restricted' => 'boolean', 'birth_date_from' => 'date:Y-m-d', 'birth_date_to' => 'date:Y-m-d'];
    }

    public function requiresPhotoOrder(): bool
    {
        return $this->photos_grouped || $this->photo_order_required;
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function ageEligibilityRule(): BelongsTo
    {
        return $this->belongsTo(AgeEligibilityRule::class)->withTrashed();
    }

    public function genders(): BelongsToMany
    {
        return $this->belongsToMany(ParticipantGender::class, 'competition_category_gender');
    }

    public function memberGroups(): BelongsToMany
    {
        return $this->belongsToMany(MemberGroup::class, 'competition_category_member_group');
    }

    public function captureDevices(): BelongsToMany
    {
        return $this->belongsToMany(CaptureDevice::class, 'competition_category_capture_device');
    }

    public function processingMethods(): BelongsToMany
    {
        return $this->belongsToMany(ProcessingMethod::class, 'competition_category_processing_method');
    }

    public function awards(): HasMany
    {
        return $this->hasMany(CompetitionCategoryAward::class)->orderBy('sort_order');
    }

    public function jurorAssignments(): HasMany
    {
        return $this->hasMany(CompetitionCategoryJurorAssignment::class)->orderBy('sort_order');
    }

    public function evaluationCriteria(): HasMany
    {
        return $this->hasMany(CompetitionCategoryEvaluationCriterion::class)->orderBy('sort_order');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(CompetitionSubmission::class);
    }
}
