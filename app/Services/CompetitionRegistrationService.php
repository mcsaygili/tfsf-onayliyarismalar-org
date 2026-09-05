<?php

namespace App\Services;

use App\Jobs\ScanRegistrationDocument;
use App\Models\Competition;
use App\Models\CompetitionRegistration;
use App\Models\CompetitionRegistrationDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CompetitionRegistrationService
{
    public function configure(Competition $competition, array $settings): void
    {
        DB::transaction(function () use ($competition, $settings) {
            $competition = CompetitionMutationLock::acquire($competition->id);
            abort_unless($competition->isEditable(), 403);
            $data = Validator::make($settings, [
                'registration_required' => ['required', 'boolean'],
                'registration_document_min' => ['required', 'integer', 'between:0,3'],
                'registration_reviewer' => ['required', 'in:institution,representative'],
            ])->validate();
            $competition->fill($data);
            if ($competition->isDirty(array_keys($data)) && CompetitionRegistration::where('competition_id', $competition->id)->exists()) {
                $this->invalid('settings_locked');
            }
            $competition->save();
        });
    }

    public function isApproved(Competition $competition, string $userId): bool
    {
        if (! $competition->registration_required) {
            return true;
        }
        $registration = CompetitionRegistration::where('competition_id', $competition->id)->where('user_id', $userId)->where('status', 'approved')->with(['documents' => fn ($query) => $query->where('is_current', true)])->first();

        return $registration && ($registration->documents_waived || $registration->documents->count() >= $registration->document_min)
            && $registration->documents->count() <= 3 && $registration->documents->every(fn ($document) => $document->isTrusted());
    }

    public function register(Competition $competition, User $user): CompetitionRegistration
    {
        return DB::transaction(function () use ($competition, $user) {
            $competition = CompetitionMutationLock::acquire($competition->id);
            abort_unless($competition->registration_required, 404);
            $check = app(MemberEligibilityService::class)->forCompetition($competition, $user->fresh(), false);
            if (! $check['eligible'] || $competition->infrastructure_provider->value !== 'tfsf' || ! $competition->newQuery()->whereKey($competition->id)->publiclyVisible()->exists()) {
                $this->invalid('closed');
            }
            $existing = CompetitionRegistration::where('competition_id', $competition->id)->where('user_id', $user->id)->first();
            if ($existing) {
                return $existing;
            }
            $competition->increment('registration_sequence');
            $registration = CompetitionRegistration::create([
                'competition_id' => $competition->id, 'user_id' => $user->id, 'number' => $competition->registration_sequence,
                'reviewer' => $competition->registration_reviewer, 'document_min' => $competition->registration_document_min,
            ]);
            $this->event($registration, $user, 'registered');

            return $registration;
        });
    }

    public function upload(CompetitionRegistration $registration, User $user, int $version, int $slot, UploadedFile $file): CompetitionRegistrationDocument
    {
        abort_unless($registration->user_id === $user->id, 404);
        if ($slot < 1 || $slot > 3 || ! $file->isValid() || $file->getSize() < 12 || $file->getSize() > 10 * 1024 * 1024 || $file->getMimeType() !== 'application/pdf') {
            $this->invalid('invalid_pdf');
        }
        $bytes = $file->getContent();
        if (! preg_match('/\A%PDF-1\.[0-9]|\A%PDF-2\.0/', $bytes) || ! preg_match('/%%EOF\s*\z/', $bytes)) {
            $this->invalid('invalid_pdf');
        }

        return DB::transaction(function () use ($registration, $user, $version, $slot, $bytes) {
            $registration = $this->lock($registration, $version);
            $this->editable($registration, $user);
            $hash = hash('sha256', $bytes);
            if ($registration->documents()->where('is_current', true)->where('slot', '!=', $slot)->where('sha256', $hash)->exists()) {
                $this->invalid('duplicate_document');
            }
            $documentVersion = ($registration->documents()->where('slot', $slot)->max('version') ?? 0) + 1;
            $path = 'registration-documents/'.$registration->id.'/'.Str::uuid().'.pdf';
            $disk = Storage::disk('local');
            DB::afterRollBack(fn () => $disk->delete($path));
            if (! $disk->put($path, $bytes)) {
                throw new \RuntimeException('Registration document could not be stored.');
            }
            $registration->documents()->where('slot', $slot)->where('is_current', true)->update(['is_current' => false]);
            $document = $registration->documents()->create(['slot' => $slot, 'version' => $documentVersion, 'disk_path' => $path, 'sha256' => $hash, 'file_size_bytes' => strlen($bytes)]);
            $registration->increment('version');
            $this->event($registration, $user, 'document_uploaded', ['document_id' => $document->id, 'slot' => $slot]);
            DB::afterCommit(function () use ($document): void {
                try {
                    ScanRegistrationDocument::dispatch($document->id);
                } catch (\Throwable) {
                    Log::warning('Document scan dispatch failed; pending scan will be recovered.', ['document_id' => $document->id]);
                }
            });

            return $document;
        });
    }

    public function removeDocument(CompetitionRegistrationDocument $document, User $user, int $version): void
    {
        abort_unless($document->registration->user_id === $user->id, 404);
        DB::transaction(function () use ($document, $user, $version) {
            $registration = $this->lock($document->registration, $version);
            $this->editable($registration, $user);
            $document = $registration->documents()->whereKey($document->id)->where('is_current', true)->firstOrFail();
            $document->update(['is_current' => false]);
            $registration->increment('version');
            $this->event($registration, $user, 'document_removed', ['document_id' => $document->id]);
        });
    }

    public function submit(CompetitionRegistration $registration, User $user, int $version): void
    {
        abort_unless($registration->user_id === $user->id, 404);
        DB::transaction(function () use ($registration, $user, $version) {
            $registration = $this->lock($registration, $version);
            $this->editable($registration, $user);
            $this->documentsComplete($registration);
            $registration->update(['status' => 'pending', 'version' => $registration->version + 1, 'submitted_at' => now(), 'reviewed_at' => null, 'reviewed_by_type' => null, 'reviewed_by_id' => null, 'review_note' => null, 'documents_waived' => false, 'approval_source' => 'normal', 'exception_grant_id' => null]);
            $this->event($registration, $user, 'submitted', ['documents' => $registration->documents()->where('is_current', true)->pluck('id')->all()]);
        });
    }

    public function decide(CompetitionRegistration $registration, Model $actor, int $version, string $decision, ?string $note): void
    {
        DB::transaction(function () use ($registration, $actor, $version, $decision, $note) {
            $registration = $this->lock($registration, $version);
            Gate::forUser($actor->fresh())->authorize('review', $registration);
            if (! in_array($decision, ['approved', 'rejected', 'changes_requested'], true) || ($decision !== 'approved' && blank($note)) || mb_strlen($note ?? '') > 2000) {
                $this->invalid('decision_required');
            }
            $competition = $registration->competition;
            if (! $competition->newQuery()->whereKey($competition->id)->publiclyVisible()->exists() || $competition->results_published_at || $competition->evaluationRounds()->where(fn ($q) => $q->where('is_final', true)->orWhere('round_number', '>', 1))->exists()) {
                $this->invalid('closed');
            }
            // Revoking a used approval needs a separate explicit participation cancellation workflow.
            $revoking = $registration->status === 'approved' && $decision === 'changes_requested';
            if ($revoking && $competition->entries()->where('user_id', $registration->user_id)->whereNotNull('submitted_at')->exists()) {
                $this->invalid('approval_in_use');
            }
            if ($registration->status !== 'pending' && ! $revoking) {
                $this->invalid('locked');
            }
            if ($decision === 'approved') {
                $this->documentsComplete($registration);
            }
            $before = $registration->status;
            $registration->update(['status' => $decision, 'version' => $registration->version + 1, 'reviewed_at' => now(), 'reviewed_by_type' => $actor::class, 'reviewed_by_id' => $actor->id, 'review_note' => $note, 'documents_waived' => false, 'approval_source' => 'normal', 'exception_grant_id' => null]);
            $this->event($registration, $actor, $decision, ['previous_status' => $before, 'note' => $note, 'documents' => $registration->documents()->where('is_current', true)->pluck('id')->all()]);
        });
    }

    public function approveDirectly(Competition $competition, Model $actor, User $member, int $version, int $grantVersion, bool $waiveDocuments, string $reason): CompetitionRegistration
    {
        return DB::transaction(function () use ($competition, $actor, $member, $version, $grantVersion, $waiveDocuments, $reason) {
            $competition = CompetitionMutationLock::acquire($competition->id);
            $exceptions = app(RegistrationExceptionService::class);
            $grant = $exceptions->authorize($competition, $actor, $grantVersion);
            $exceptions->requireReason($reason);
            $member = $member->fresh();
            if (! $member || ! app(MemberEligibilityService::class)->forCompetition($competition, $member, false)['eligible']
                || $competition->infrastructure_provider->value !== 'tfsf'
                || ! $competition->newQuery()->whereKey($competition->id)->publiclyVisible()->exists()
                || $competition->results_published_at
                || $competition->evaluationRounds()->where(fn ($q) => $q->where('is_final', true)->orWhere('round_number', '>', 1))->exists()) {
                $this->invalid('closed');
            }
            $registration = CompetitionRegistration::where('competition_id', $competition->id)->where('user_id', $member->id)->lockForUpdate()->first();
            if (($registration?->version ?? 0) !== $version) {
                $this->invalid('stale');
            }
            if ($registration && ! in_array($registration->status, ['draft', 'pending', 'changes_requested', 'rejected'], true)) {
                $this->invalid('locked');
            }
            $before = $registration?->status;
            if (! $registration) {
                $competition->increment('registration_sequence');
                $registration = CompetitionRegistration::create([
                    'competition_id' => $competition->id, 'user_id' => $member->id, 'number' => $competition->registration_sequence,
                    'reviewer' => $competition->registration_reviewer, 'document_min' => $competition->registration_document_min,
                ]);
                $this->event($registration, $actor, 'direct_registered');
            }
            // A minimum-count waiver never waives the safety/integrity of present documents.
            $this->documentsComplete($registration, $waiveDocuments);
            $registration->update([
                'status' => 'approved', 'version' => $registration->version + 1,
                'submitted_at' => $registration->submitted_at ?? now(), 'reviewed_at' => now(),
                'reviewed_by_type' => $actor::class, 'reviewed_by_id' => $actor->id, 'review_note' => trim($reason),
                'approval_source' => 'direct', 'documents_waived' => $waiveDocuments, 'exception_grant_id' => $grant->id,
            ]);
            $this->event($registration, $actor, 'exception_approved', [
                'previous_status' => $before, 'note' => trim($reason), 'documents_waived' => $waiveDocuments,
                'grant_id' => $grant->id, 'grant_version' => $grant->version,
                'documents' => $registration->documents()->where('is_current', true)->pluck('id')->all(),
            ]);

            return $registration;
        });
    }

    private function lock(CompetitionRegistration $registration, int $version): CompetitionRegistration
    {
        $competition = CompetitionMutationLock::acquire($registration->competition_id);
        $registration = CompetitionRegistration::whereKey($registration->id)->lockForUpdate()->firstOrFail()->setRelation('competition', $competition);
        if ($registration->version !== $version) {
            $this->invalid('stale');
        }

        return $registration;
    }

    private function editable(CompetitionRegistration $registration, User $user): void
    {
        abort_unless($registration->user_id === $user->id, 404);
        if (! in_array($registration->status, ['draft', 'changes_requested'], true)) {
            $this->invalid('locked');
        }
        if (! $registration->competition->newQuery()->whereKey($registration->competition_id)->publiclyVisible()->exists() || ! app(MemberEligibilityService::class)->forCompetition($registration->competition, $user->fresh(), false)['eligible']) {
            $this->invalid('closed');
        }
    }

    private function documentsComplete(CompetitionRegistration $registration, bool $waiveMinimum = false): void
    {
        $documents = $registration->documents()->where('is_current', true)->get();
        if ((! $waiveMinimum && $documents->count() < $registration->document_min) || $documents->count() > 3) {
            $this->invalid('documents_required');
        }
        foreach ($documents as $document) {
            if (! $document->isTrusted()) {
                $this->invalid('scan_required');
            }
            if (! Storage::disk('local')->exists($document->disk_path) || ! hash_equals($document->sha256, hash_file('sha256', Storage::disk('local')->path($document->disk_path)))) {
                $this->invalid('document_unavailable');
            }
        }
    }

    private function event(CompetitionRegistration $registration, Model $actor, string $event, array $context = []): void
    {
        $registration->events()->create(['event' => $event, 'version' => $registration->version, 'actor_type' => $actor::class, 'actor_id' => $actor->id, 'context' => $context]);
    }

    private function invalid(string $key): never
    {
        throw ValidationException::withMessages(['registration' => __('registration.'.$key)]);
    }
}
