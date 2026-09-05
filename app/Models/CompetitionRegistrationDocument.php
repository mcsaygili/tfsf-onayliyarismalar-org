<?php

namespace App\Models;

use App\Support\Documents\PdfDocumentScanner;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['slot', 'version', 'is_current', 'disk_path', 'sha256', 'file_size_bytes'])]
class CompetitionRegistrationDocument extends Model
{
    use HasUuids;

    protected $attributes = ['is_current' => true, 'scan_status' => 'pending', 'scan_attempts' => 0];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['is_current' => 'boolean', 'version' => 'integer', 'slot' => 'integer', 'scan_started_at' => 'datetime', 'scanned_at' => 'datetime', 'scan_attempts' => 'integer'];
    }

    public function isTrusted(): bool
    {
        return $this->scan_status === 'clean' && $this->scanned_at !== null
            && $this->scan_policy === PdfDocumentScanner::POLICY
            && is_string($this->scan_sha256) && hash_equals($this->sha256, $this->scan_sha256);
    }

    public function scanDisplayStatus(): string
    {
        return $this->scan_status === 'clean' && ! $this->isTrusted() ? 'pending' : $this->scan_status;
    }

    public function registration()
    {
        return $this->belongsTo(CompetitionRegistration::class, 'registration_id');
    }
}
