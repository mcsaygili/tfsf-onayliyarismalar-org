<?php

namespace App\Support\CompetitionWizard;

use App\Models\AgeEligibilityRule;
use App\Models\CaptureDevice;
use App\Models\Competition;
use App\Models\CompetitionCategory;
use App\Models\MemberGroup;
use App\Models\ParticipantGender;
use App\Models\ProcessingMethod;
use App\Support\Photo\CategoryPhotoRules;
use App\Support\Photo\SubmissionDeclarations;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** Adım 6 — Kategori bazlı katılımcı ve fotoğraf uygunluk kuralları. */
class Step6 implements CompetitionStep
{
    public function number(): int
    {
        return 6;
    }

    public function label(): string
    {
        return __('institution.competitions.steps.6.label');
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
        $competition->loadMissing('categories.translations', 'categories.ageEligibilityRule.translations', 'categories.genders', 'categories.memberGroups', 'categories.captureDevices', 'categories.processingMethods');

        return ['categories' => $competition->categories->map(fn (CompetitionCategory $category) => [
            'id' => $category->id,
            'photo_rules' => CategoryPhotoRules::normalize($category->photo_rules),
            'photo_story_required' => $category->photo_story_required,
            'category_story_required' => $category->category_story_required,
            'photo_order_required' => $category->photo_order_required,
            'max_photos_per_participant' => $category->max_photos_per_participant,
            'tr' => ['name' => $category->getTranslation('tr', false)?->name],
            'en' => ['name' => $category->getTranslation('en', false)?->name],
            'age_eligibility_rule' => $category->age_eligibility_rule_id,
            'gender_id' => $category->genders->first()?->id,
            'member_group_match_mode' => $category->member_group_match_mode,
            'member_group_ids' => $category->memberGroups->pluck('id')->all(),
            'capture_device_ids' => $category->captureDevices->pluck('id')->all(),
            'processing_method_ids' => $category->processingMethods->pluck('id')->all(),
        ])->values()->all()];
    }

    public function persist(Competition $competition, array $validated): void
    {
        if (! array_key_exists('categories', $validated)) {
            return;
        }

        $keptIds = [];
        foreach ($validated['categories'] as $index => $payload) {
            $category = isset($payload['id'])
                ? $competition->categories()->whereKey($payload['id'])->firstOrFail()
                : new CompetitionCategory(['competition_id' => $competition->id]);

            $category->fill([
                'sort_order' => ($index + 1) * 10,
                'max_photos_per_participant' => $payload['max_photos_per_participant'] ?? 4,
                'age_eligibility_rule_id' => $payload['age_eligibility_rule'] ?? null,
                'member_group_match_mode' => $payload['member_group_match_mode'] ?? 'any',
                'birth_date_restricted' => false,
                'birth_date_from' => null,
                'birth_date_to' => null,
            ]);
            if (array_key_exists('photo_rules', $payload)) {
                $category->photo_rules = CategoryPhotoRules::normalize($payload['photo_rules']);
            }
            foreach (SubmissionDeclarations::CATEGORY_FLAGS as $flag) {
                if (array_key_exists($flag, $payload)) {
                    $category->{$flag} = (bool) $payload[$flag];
                }
            }
            $category->save();
            $keptIds[] = $category->id;

            foreach (['tr', 'en'] as $locale) {
                $name = trim((string) data_get($payload, "$locale.name", ''));
                if ($name !== '') {
                    $category->translations()->updateOrCreate(['locale' => $locale], ['name' => $name]);
                } else {
                    $category->translations()->where('locale', $locale)->delete();
                }
            }

            $category->genders()->sync(isset($payload['gender_id']) ? [$payload['gender_id']] : []);
            $category->memberGroups()->sync($payload['member_group_ids'] ?? []);
            $category->captureDevices()->sync($payload['capture_device_ids'] ?? []);
            $category->processingMethods()->sync($payload['processing_method_ids'] ?? []);
        }

        $competition->categories()->whereNotIn('id', $keptIds)->delete();
        $competition->unsetRelation('categories');
    }

    public function rules(bool $isDraftSave, Competition $competition): array
    {
        $required = $isDraftSave ? 'nullable' : 'required';
        $categoryId = Rule::exists('competition_categories', 'id')->where('competition_id', $competition->id)->whereNull('deleted_at');
        $activeReference = fn (string $table) => Rule::exists($table, 'id')->where('status', true)->whereNull('deleted_at');
        $exclusiveNoCheck = function (string $table, string $code) {
            return function (string $attribute, mixed $value, \Closure $fail) use ($table, $code) {
                $noCheckId = DB::table($table)->where('code', $code)->value('id');
                if (is_array($value) && count($value) > 1 && in_array($noCheckId, $value, true)) {
                    $fail(__('institution.competitions.validation.no_check_exclusive'));
                }
            };
        };

        return [
            ...$this->photoRules(),
            'categories' => [$required, 'array', $isDraftSave ? 'max:20' : 'min:1', 'max:20'],
            'categories.*.photo_story_required' => ['sometimes', 'boolean'],
            'categories.*.category_story_required' => ['sometimes', 'boolean'],
            'categories.*.photo_order_required' => ['sometimes', 'boolean'],
            'categories.*.id' => ['nullable', 'uuid', $categoryId],
            'categories.*.max_photos_per_participant' => ['sometimes', 'integer', 'min:1', 'max:20'],
            'categories.*.tr.name' => [$required, 'string', 'max:255', 'distinct:ignore_case'],
            'categories.*.en.name' => [$isDraftSave || ! $competition->requiresEnglishContent() ? 'nullable' : 'required', 'string', 'max:255', 'distinct:ignore_case'],
            'categories.*.age_eligibility_rule' => [$required, 'uuid', $activeReference('age_eligibility_rules')],
            'categories.*.gender_id' => [$required, 'uuid', $activeReference('participant_genders')],
            'categories.*.member_group_match_mode' => [$required, Rule::in(['any', 'all'])],
            'categories.*.member_group_ids' => [$required, 'array', $isDraftSave ? 'max:30' : 'min:1', 'max:30', $exclusiveNoCheck('member_groups', 'no-membership-check')],
            'categories.*.member_group_ids.*' => ['uuid', 'distinct', $activeReference('member_groups')],
            'categories.*.capture_device_ids' => [$required, 'array', $isDraftSave ? 'max:30' : 'min:1', 'max:30', $exclusiveNoCheck('capture_devices', 'no-device-check')],
            'categories.*.capture_device_ids.*' => ['uuid', 'distinct', $activeReference('capture_devices')],
            'categories.*.processing_method_ids' => [$required, 'array', $isDraftSave ? 'max:30' : 'min:1', 'max:30', $exclusiveNoCheck('processing_methods', 'no-processing-check')],
            'categories.*.processing_method_ids.*' => ['uuid', 'distinct', $activeReference('processing_methods')],
        ];
    }

    private function photoRules(): array
    {
        $maxMb = config('competition-photos.max_file_size_mb');
        $prefix = 'categories.*.photo_rules';

        return [
            $prefix => ['sometimes', 'array:formats,'.implode(',', CategoryPhotoRules::LIMITS), function (string $attribute, mixed $value, \Closure $fail) {
                if (! is_array($value)) {
                    return;
                }
                foreach ([['min_file_size_mb', 'max_file_size_mb'], ['min_short_edge', 'max_long_edge'], ['min_dpi', 'max_dpi']] as [$min, $max]) {
                    if (is_numeric($value[$min] ?? null) && is_numeric($value[$max] ?? null)
                        && $value[$max] > 0 && $value[$min] > $value[$max]) {
                        $fail(__('photo_rules.invalid_range', ['min' => __('photo_rules.fields.'.$min), 'max' => __('photo_rules.fields.'.$max)]));
                    }
                }
            }],
            "$prefix.formats" => ['required_with:'.$prefix, 'array', 'min:1', 'max:3'],
            "$prefix.formats.*" => ['required', 'distinct', Rule::in(CategoryPhotoRules::FORMATS)],
            "$prefix.min_file_size_mb" => ['nullable', 'numeric', 'min:0', 'max:'.$maxMb, 'decimal:0,3'],
            "$prefix.max_file_size_mb" => ['nullable', 'numeric', 'min:0', 'max:'.$maxMb, 'decimal:0,3'],
            "$prefix.min_short_edge" => ['nullable', 'integer', 'min:0', 'max:100000'],
            "$prefix.max_long_edge" => ['nullable', 'integer', 'min:0', 'max:100000'],
            "$prefix.min_dpi" => ['nullable', 'integer', 'min:0', 'max:100000'],
            "$prefix.max_dpi" => ['nullable', 'integer', 'min:0', 'max:100000'],
        ];
    }

    public function formData(Competition $competition): array
    {
        $categories = old('categories', $this->data($competition)['categories']);

        return $categories ?: [[
            'id' => null, 'tr' => ['name' => ''], 'en' => ['name' => ''],
            'max_photos_per_participant' => 4,
            'photo_rules' => CategoryPhotoRules::defaults(),
            'age_eligibility_rule' => null, 'gender_id' => null,
            'member_group_match_mode' => 'any', 'member_group_ids' => [], 'capture_device_ids' => [], 'processing_method_ids' => [],
        ]];
    }

    /** @return array{genders: Collection, ageRules: Collection, memberGroups: Collection, captureDevices: Collection, processingMethods: Collection} */
    public function options(): array
    {
        return [
            'genders' => ParticipantGender::active()->ordered()->with('translations')->get(),
            'ageRules' => AgeEligibilityRule::active()->ordered()->with('translations')->get(),
            'memberGroups' => MemberGroup::active()->ordered()->with('translations')->get(),
            'captureDevices' => CaptureDevice::active()->ordered()->with('translations')->get(),
            'processingMethods' => ProcessingMethod::active()->ordered()->with('translations')->get(),
        ];
    }
}
