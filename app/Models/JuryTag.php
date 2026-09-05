<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['juror_id', 'competition_category_id', 'name', 'name_key', 'color'])]
class JuryTag extends Model
{
    use HasUuids;

    public function photos(): BelongsToMany
    {
        return $this->belongsToMany(CompetitionSubmissionPhoto::class, 'jury_tag_photos', 'jury_tag_id', 'submission_photo_id')->withTimestamps();
    }
}
