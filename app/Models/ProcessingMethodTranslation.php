<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['processing_method_id', 'locale', 'name', 'description'])]
class ProcessingMethodTranslation extends Model
{
    use HasUuids;
}
