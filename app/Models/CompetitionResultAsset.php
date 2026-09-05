<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['source_photo_id', 'disk_path', 'sha256', 'mime_type', 'file_size_bytes', 'owner_user_id', 'is_public'])]
class CompetitionResultAsset extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected function casts(): array
    {
        return ['is_public' => 'boolean'];
    }

    public function publication()
    {
        return $this->belongsTo(CompetitionResultPublication::class, 'publication_id');
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Published image records are immutable.'));
    }
}
