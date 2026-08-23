<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['age_eligibility_rule_id', 'locale', 'name', 'description'])]
class AgeEligibilityRuleTranslation extends Model
{
    use HasUuids;
}
