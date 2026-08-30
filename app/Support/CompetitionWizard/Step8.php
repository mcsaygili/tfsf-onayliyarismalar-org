<?php

namespace App\Support\CompetitionWizard;

use App\Models\Competition;
use App\Models\CompetitionCategory;
use App\Models\CompetitionCategoryJurorAssignment;
use App\Models\Juri;
use App\Models\JuryInvitation;
use App\Services\JuryInvitationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Adım 8 — Her yarışma kategorisi için jüri üyelerinin atanması. */
class Step8 implements CompetitionStep
{
    public function number(): int
    {
        return 8;
    }

    public function label(): string
    {
        return __('institution.competitions.steps.8.label');
    }

    public function isImplemented(): bool
    {
        return true;
    }

    public function isApplicable(Competition $competition): bool
    {
        return true;
    }

    public function data(Competition $competition): array
    {
        $competition->loadMissing([
            'categories.translations',
            'categories.jurorAssignments.juror',
            'categories.jurorAssignments.invitation',
        ]);

        return ['categories' => $competition->categories->mapWithKeys(fn (CompetitionCategory $category) => [
            $category->id => ['jurors' => $category->jurorAssignments->map(function (CompetitionCategoryJurorAssignment $assignment) {
                if ($assignment->juror) {
                    return [
                        'assignment_id' => $assignment->id,
                        'type' => 'existing',
                        'juror_id' => $assignment->juror_id,
                        'invitation_id' => null,
                        'first_name' => $assignment->juror->first_name,
                        'last_name' => $assignment->juror->last_name,
                        'email' => $assignment->juror->email,
                        'locale' => 'tr',
                        'status' => 'registered',
                    ];
                }

                $invitation = $assignment->invitation;

                return [
                    'assignment_id' => $assignment->id,
                    'type' => 'invitation',
                    'juror_id' => null,
                    'invitation_id' => $assignment->jury_invitation_id,
                    'first_name' => $invitation?->first_name,
                    'last_name' => $invitation?->last_name,
                    'email' => $invitation?->email,
                    'locale' => $invitation?->locale ?: 'tr',
                    'status' => $invitation?->sent_at ? 'invited' : 'draft',
                ];
            })->values()->all()],
        ])->all()];
    }

    public function persist(Competition $competition, array $validated): void
    {
        if (! array_key_exists('categories', $validated)) {
            return;
        }

        $competition->loadMissing('categories');
        $staffId = Auth::guard('institution')->id();

        foreach ($validated['categories'] as $categoryId => $payload) {
            $category = $competition->categories->firstWhere('id', $categoryId);
            abort_unless($category, 422);
            $keptIds = [];

            foreach ($payload['jurors'] ?? [] as $index => $jurorData) {
                $assignment = filled($jurorData['assignment_id'] ?? null)
                    ? $category->jurorAssignments()->whereKey($jurorData['assignment_id'])->firstOrFail()
                    : new CompetitionCategoryJurorAssignment(['competition_category_id' => $category->id]);

                if ($jurorData['type'] === 'existing') {
                    $juror = Juri::query()
                        ->whereKey($jurorData['juror_id'])
                        ->where('status', true)
                        ->whereNotNull('email_verified_at')
                        ->firstOrFail();
                    $assignment->fill([
                        'juror_id' => $juror->id,
                        'jury_invitation_id' => null,
                    ]);
                } else {
                    $email = Str::lower(trim($jurorData['email']));
                    $registeredJuror = Juri::query()
                        ->whereRaw('LOWER(email) = ?', [$email])
                        ->where('status', true)
                        ->whereNotNull('email_verified_at')
                        ->first();

                    if ($registeredJuror) {
                        $assignment->fill([
                            'juror_id' => $registeredJuror->id,
                            'jury_invitation_id' => null,
                        ]);
                    } else {
                        $invitation = filled($jurorData['invitation_id'] ?? null)
                            ? $competition->juryInvitations()->whereKey($jurorData['invitation_id'])->firstOrFail()
                            : $competition->juryInvitations()->firstOrNew(['email' => $email]);

                        abort_if($invitation->accepted_at !== null, 422);
                        $invitation->fill([
                            'institution_id' => $competition->institution_id,
                            'invited_by' => $staffId,
                            'email' => $email,
                            'first_name' => trim($jurorData['first_name']),
                            'last_name' => trim($jurorData['last_name']),
                            'locale' => $jurorData['locale'],
                        ]);
                        $invitation->forceFill(['revoked_at' => null])->save();

                        $assignment->fill([
                            'juror_id' => null,
                            'jury_invitation_id' => $invitation->id,
                        ]);
                    }
                }

                $assignment->fill([
                    'assigned_by' => $staffId,
                    'sort_order' => ($index + 1) * 10,
                ])->save();
                $keptIds[] = $assignment->id;
            }

            $category->jurorAssignments()->whereNotIn('id', $keptIds)->delete();
        }

        $competition->juryInvitations()
            ->whereNull('accepted_at')
            ->whereDoesntHave('assignments')
            ->update([
                'revoked_at' => now(),
                'token_hash' => null,
                'expires_at' => null,
            ]);
    }

    public function rules(bool $isDraftSave, Competition $competition): array
    {
        $categoryIds = $competition->categories()->pluck('id')->all();
        $required = $isDraftSave ? 'nullable' : 'required';

        return [
            'categories' => [
                $required,
                'array',
                $isDraftSave ? 'max:20' : 'min:1',
                'max:20',
                function (string $attribute, mixed $value, \Closure $fail) use ($categoryIds, $isDraftSave) {
                    if (! is_array($value)) {
                        return;
                    }
                    $submitted = array_keys($value);
                    if (array_diff($submitted, $categoryIds) !== [] || (! $isDraftSave && array_diff($categoryIds, $submitted) !== [])) {
                        $fail(__('institution.competitions.validation.category_jurors_mismatch'));
                    }
                },
            ],
            'categories.*.jurors' => [
                $required,
                'array',
                $isDraftSave ? 'max:15' : 'min:1',
                'max:15',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (! is_array($value)) {
                        return;
                    }
                    $identities = collect($value)->map(fn ($row) => ($row['type'] ?? null) === 'existing'
                        ? 'juror:'.($row['juror_id'] ?? '')
                        : 'email:'.Str::lower(trim((string) ($row['email'] ?? ''))));
                    if ($identities->filter()->duplicates()->isNotEmpty()) {
                        $fail(__('institution.competitions.validation.category_juror_duplicate'));
                    }
                },
            ],
            'categories.*.jurors.*.assignment_id' => [
                'nullable',
                'uuid',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (! $value) {
                        return;
                    }
                    $categoryId = explode('.', $attribute)[1] ?? null;
                    if (! DB::table('competition_category_juror_assignments')->where('id', $value)->where('competition_category_id', $categoryId)->exists()) {
                        $fail(__('institution.competitions.validation.category_juror_invalid'));
                    }
                },
            ],
            'categories.*.jurors.*.type' => [$required, Rule::in(['existing', 'invitation'])],
            'categories.*.jurors.*.juror_id' => [
                'nullable',
                'required_if:categories.*.jurors.*.type,existing',
                'uuid',
                Rule::exists('jurors', 'id')->where(fn ($query) => $query->where('status', true)->whereNotNull('email_verified_at')),
            ],
            'categories.*.jurors.*.invitation_id' => [
                'nullable',
                'uuid',
                function (string $attribute, mixed $value, \Closure $fail) use ($competition) {
                    if (! $value) {
                        return;
                    }
                    if (! $competition->juryInvitations()->whereKey($value)->whereNull('accepted_at')->exists()) {
                        $fail(__('institution.competitions.validation.jury_invitation_invalid'));
                    }
                },
            ],
            'categories.*.jurors.*.first_name' => ['nullable', 'required_if:categories.*.jurors.*.type,invitation', 'string', 'max:255'],
            'categories.*.jurors.*.last_name' => ['nullable', 'required_if:categories.*.jurors.*.type,invitation', 'string', 'max:255'],
            'categories.*.jurors.*.email' => [
                'nullable',
                'required_if:categories.*.jurors.*.type,invitation',
                'string',
                'lowercase',
                'email',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (! filled($value)) {
                        return;
                    }

                    $juror = Juri::query()
                        ->whereRaw('LOWER(email) = ?', [Str::lower(trim((string) $value))])
                        ->first();

                    if ($juror && (! $juror->status || $juror->email_verified_at === null)) {
                        $fail(__('institution.competitions.validation.jury_email_unavailable'));
                    }
                },
            ],
            'categories.*.jurors.*.locale' => ['nullable', 'required_if:categories.*.jurors.*.type,invitation', Rule::in(['tr', 'en'])],
            'categories.*.jurors.*.status' => ['nullable', 'string'],
        ];
    }

    public function formData(Competition $competition): array
    {
        $data = old('categories', $this->data($competition)['categories']);

        foreach ($competition->categories as $category) {
            $data[$category->id] ??= ['jurors' => []];
            $data[$category->id]['jurors'] = array_values($data[$category->id]['jurors'] ?? []);
        }

        return $data;
    }

    public function sendPendingInvitations(Competition $competition, JuryInvitationService $service): void
    {
        $competition->juryInvitations()
            ->whereNull('sent_at')
            ->whereNull('accepted_at')
            ->whereNull('revoked_at')
            ->whereHas('assignments')
            ->get()
            ->each(fn (JuryInvitation $invitation) => $service->send($invitation));
    }
}
