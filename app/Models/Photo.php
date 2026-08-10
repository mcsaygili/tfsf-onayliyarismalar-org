<?php

namespace App\Models;

use Database\Factories\PhotoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Üye (fotoğrafçı) kişisel portfolyosundaki tek bir fotoğraf — bkz. proje
 * planı "Üye Paneli — Fotoğraf Portfolyosu". `exif_*` alanları
 * `App\Support\Photo\ExifReader` tarafından yükleme sırasında doldurulur;
 * hiçbiri bulunamazsa `exif_missing = true` olur.
 */
#[Fillable([
    'user_id', 'photo_category_id', 'title', 'location', 'taken_at',
    'tags', 'tags_text', 'description',
    'disk_path', 'thumb_path', 'original_filename', 'mime_type', 'file_size_bytes', 'width', 'height',
    'camera_make', 'camera_model', 'lens', 'focal_length', 'aperture', 'shutter_speed', 'iso',
    'exif_captured_at', 'color_space', 'exif_raw', 'exif_missing',
])]
class Photo extends Model
{
    /** @use HasFactory<PhotoFactory> */
    use HasFactory, HasUuids;

    public const MAX_PER_USER = 30;

    protected function casts(): array
    {
        return [
            'taken_at' => 'date',
            'tags' => 'array',
            'exif_raw' => 'array',
            'exif_captured_at' => 'datetime',
            'exif_missing' => 'boolean',
            'file_size_bytes' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'iso' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PhotoCategory::class, 'photo_category_id');
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->disk_path);
    }

    public function thumbUrl(): string
    {
        return $this->thumb_path
            ? Storage::disk('public')->url($this->thumb_path)
            : $this->url();
    }
}
