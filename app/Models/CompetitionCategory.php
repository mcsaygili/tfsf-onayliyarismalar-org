<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['competition_id', 'sort_order', 'age_eligibility_rule_id', 'member_group_match_mode', 'birth_date_restricted', 'birth_date_from', 'birth_date_to'])]
class CompetitionCategory extends Model
{
    use HasTranslations, HasUuids, SoftDeletes;

    protected array $translatedAttributes = ['name'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer', 'birth_date_restricted' => 'boolean', 'birth_date_from' => 'date:Y-m-d', 'birth_date_to' => 'date:Y-m-d'];
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
}
