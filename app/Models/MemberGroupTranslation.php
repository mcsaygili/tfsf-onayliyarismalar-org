<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['member_group_id', 'locale', 'name', 'description'])]
class MemberGroupTranslation extends Model
{
    use HasUuids;
}
