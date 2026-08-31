<?php

namespace App\Support\CompetitionWizard;

use App\Models\Competition;
use App\Models\CompetitionCategory;
use App\Models\CompetitionCategoryEvaluationCriterion;
use App\Models\CompetitionCategoryJurorAssignment;
use App\Models\EvaluationCriterion;
use App\Models\Juri;
use App\Models\JuryInvitation;
use App\Services\JuryInvitationService;
use Illuminate\Database\Eloquent\Collection;
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
            'categories.evaluationCriteria.criterion.translations',
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
                    'status' => $invitation?->status()->value ?? 'draft',
                    'sent_at' => $invitation?->sent_at?->format('d.m.Y H:i'),
                    'expires_at' => $invitation?->expires_at?->format('d.m.Y H:i'),
                    'send_count' => $invitation?->send_count ?? 0,
                ];
            })->values()->all(), 'criteria' => $category->evaluationCriteria->map(fn (CompetitionCategoryEvaluationCriterion $criterion) => [
                'id' => $criterion->id,
                'evaluation_criterion_id' => $criterion->evaluation_criterion_id,
                'min_score' => $criterion->min_score,
                'max_score' => $criterion->max_score,
                'weight' => $criterion->weight,
            ])->values()->all()],
        ])->all()];
    }

    public function persist(Competition $competition, array $validated): void
    {
        if (! array_key_exists('categories', $validated)) {
            return;
        }

        $competition->loadMissing('categories');
        $staff = Auth::guard('institution')->user();
        $staffId = $staff->id;

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
                        $referencesExistingInvitation = filled($jurorData['invitation_id'] ?? null);
                        $invitation = $referencesExistingInvitation
                            ? $competition->juryInvitations()->whereKey($jurorData['invitation_id'])->firstOrFail()
                            : $competition->juryInvitations()->firstOrNew(['email' => $email]);
                        $wasNewInvitation = ! $invitation->exists;
                        $wasReopened = false;

                        abort_if($invitation->accepted_at !== null, 422);
                        if (! $referencesExistingInvitation && $invitation->exists && ! $invitation->isPending()) {
                            $wasReopened = true;
                            $invitation->forceFill([
                                'declined_at' => null,
                                'revoked_at' => null,
                                'sent_at' => null,
                                'opened_at' => null,
                                'token_hash' => null,
                                'expires_at' => null,
                            ]);
                        }
                        $invitation->fill([
                            'institution_id' => $competition->institution_id,
                            'invited_by' => $staffId,
                            'email' => $email,
                            'first_name' => trim($jurorData['first_name']),
                            'last_name' => trim($jurorData['last_name']),
                            'locale' => $jurorData['locale'],
                        ]);
                        if ($invitation->isPending()) {
                            $invitation->forceFill(['revoked_at' => null]);
                        }
                        $invitation->save();
                        if ($wasNewInvitation || $wasReopened) {
                            $invitation->events()->create([
                                'action' => $wasNewInvitation ? 'created' : 'reopened',
                                'actor_id' => $staff->id,
                                'actor_type' => $staff::class,
                            ]);
                        }

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

            $keptCriterionIds = [];
            foreach ($payload['criteria'] ?? [] as $index => $criterionData) {
                $criterion = filled($criterionData['id'] ?? null)
                    ? $category->evaluationCriteria()->whereKey($criterionData['id'])->firstOrFail()
                    : new CompetitionCategoryEvaluationCriterion(['competition_category_id' => $category->id]);

                $criterion->fill([
                    'evaluation_criterion_id' => $criterionData['evaluation_criterion_id'],
                    'min_score' => $criterionData['min_score'],
                    'max_score' => $criterionData['max_score'],
                    'weight' => $criterionData['weight'],
                    'sort_order' => ($index + 1) * 10,
                ])->save();
                $keptCriterionIds[] = $criterion->id;
            }

            $category->evaluationCriteria()->whereNotIn('id', $keptCriterionIds)->delete();
        }

        $competition->juryInvitations()
            ->whereNull('accepted_at')
            ->whereDoesntHave('assignments')
            ->get()
            ->each(function (JuryInvitation $invitation) use ($staff): void {
                if ($invitation->revoked_at === null) {
                    $invitation->forceFill([
                        'revoked_at' => now(),
                        'token_hash' => null,
                        'expires_at' => null,
                    ])->save();
                    $invitation->events()->create([
                        'action' => 'cancelled',
                        'actor_id' => $staff->id,
                        'actor_type' => $staff::class,
                        'metadata' => ['reason' => 'removed_from_all_categories'],
                    ]);
                }
            });
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
            'categories.*.criteria' => [
                $required,
                'array',
                $isDraftSave ? 'max:20' : 'min:1',
                'max:20',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (! is_array($value)) {
                        return;
                    }
                    $criterionIds = collect($value)->pluck('evaluation_criterion_id')->filter();
                    if ($criterionIds->duplicates()->isNotEmpty()) {
                        $fail(__('institution.competitions.validation.category_criterion_duplicate'));
                    }
                    $generalCriterionId = EvaluationCriterion::query()
                        ->where('code', 'general-evaluation')
                        ->where('status', true)
                        ->value('id');
                    if ($generalCriterionId && ! $criterionIds->contains($generalCriterionId)) {
                        $fail(__('institution.competitions.validation.general_criterion_required'));
                    }
                },
            ],
            'categories.*.criteria.*.id' => [
                'nullable',
                'uuid',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (! $value) {
                        return;
                    }
                    $categoryId = explode('.', $attribute)[1] ?? null;
                    if (! DB::table('competition_category_evaluation_criteria')->where('id', $value)->where('competition_category_id', $categoryId)->exists()) {
                        $fail(__('institution.competitions.validation.category_criterion_invalid'));
                    }
                },
            ],
            'categories.*.criteria.*.evaluation_criterion_id' => ['required', 'uuid', Rule::exists('evaluation_criteria', 'id')->whereNull('deleted_at')],
            'categories.*.criteria.*.min_score' => ['required', 'integer', 'min:0', 'max:9999'],
            'categories.*.criteria.*.max_score' => ['required', 'integer', 'min:1', 'max:10000'],
            'categories.*.criteria.*.weight' => ['required', 'numeric', 'gt:0', 'max:999.99'],
            'categories.*.criteria.*' => [
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (is_array($value) && isset($value['min_score'], $value['max_score']) && (int) $value['max_score'] <= (int) $value['min_score']) {
                        $fail(__('institution.competitions.validation.category_criterion_range'));

                        return;
                    }

                    if (! is_array($value) || ! filled($value['evaluation_criterion_id'] ?? null)) {
                        return;
                    }

                    $reference = EvaluationCriterion::query()->find($value['evaluation_criterion_id']);
                    if ($reference && (
                        (int) ($value['min_score'] ?? -1) !== $reference->default_min_score
                        || (int) ($value['max_score'] ?? -1) !== $reference->default_max_score
                    )) {
                        $fail(__('institution.competitions.validation.category_criterion_defaults'));
                    }
                },
            ],
        ];
    }

    public function formData(Competition $competition): array
    {
        $data = old('categories', $this->data($competition)['categories']);
        $options = $this->criteriaOptions($competition)->keyBy('id');
        $defaultCriterion = $options->firstWhere('code', 'general-evaluation');

        foreach ($competition->categories as $category) {
            $data[$category->id] ??= ['jurors' => [], 'criteria' => []];
            $data[$category->id]['jurors'] = array_values($data[$category->id]['jurors'] ?? []);
            $criteria = array_values($data[$category->id]['criteria'] ?? []);
            if ($criteria === [] && $defaultCriterion) {
                $criteria[] = [
                    'id' => '',
                    'evaluation_criterion_id' => $defaultCriterion->id,
                    'min_score' => $defaultCriterion->default_min_score,
                    'max_score' => $defaultCriterion->default_max_score,
                    'weight' => $defaultCriterion->default_weight,
                ];
            }
            $data[$category->id]['criteria'] = collect($criteria)->map(function (array $criterion) use ($options): array {
                $reference = $options->get($criterion['evaluation_criterion_id'] ?? null);
                if ($reference) {
                    $criterion['min_score'] = $reference->default_min_score;
                    $criterion['max_score'] = $reference->default_max_score;
                }

                return $criterion;
            })->values()->all();
        }

        return $data;
    }

    public function criteriaOptions(Competition $competition): Collection
    {
        $assignedIds = CompetitionCategoryEvaluationCriterion::query()
            ->whereIn('competition_category_id', $competition->categories()->pluck('id'))
            ->pluck('evaluation_criterion_id');

        return EvaluationCriterion::query()
            ->where(fn ($query) => $query->where('status', true)->orWhereIn('id', $assignedIds))
            ->whereNull('deleted_at')
            ->with('translations')
            ->ordered()
            ->get();
    }

    public function sendPendingInvitations(Competition $competition, JuryInvitationService $service): void
    {
        $competition->juryInvitations()
            ->whereNull('sent_at')
            ->whereNull('accepted_at')
            ->whereNull('declined_at')
            ->whereNull('revoked_at')
            ->whereHas('assignments')
            ->get()
            ->each(fn (JuryInvitation $invitation) => $service->send($invitation, Auth::guard('institution')->user()));
    }
}
