<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['competition_id', 'user_id', 'number', 'status', 'version', 'reviewer', 'document_min', 'submitted_at', 'reviewed_at', 'reviewed_by_type', 'reviewed_by_id', 'review_note', 'documents_waived', 'approval_source', 'exception_grant_id'])]
class CompetitionRegistration extends Model
{
    use HasUuids;

    protected $attributes = ['status' => 'draft', 'version' => 1];

    protected function casts(): array
    {
        return ['documents_waived' => 'boolean', 'submitted_at' => 'datetime', 'reviewed_at' => 'datetime', 'version' => 'integer', 'number' => 'integer', 'document_min' => 'integer'];
    }

    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function documents()
    {
        return $this->hasMany(CompetitionRegistrationDocument::class, 'registration_id');
    }

    public function events()
    {
        return $this->hasMany(CompetitionRegistrationEvent::class, 'registration_id');
    }
}
