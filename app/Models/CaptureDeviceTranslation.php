<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['capture_device_id', 'locale', 'name', 'description'])]
class CaptureDeviceTranslation extends Model
{
    use HasUuids;
}
