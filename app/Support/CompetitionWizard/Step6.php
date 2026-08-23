<?php

namespace App\Support\CompetitionWizard;

use App\Models\AgeEligibilityRule;
use App\Models\CaptureDevice;
use App\Models\Competition;
use App\Models\CompetitionCategory;
use App\Models\MemberGroup;
use App\Models\ParticipantGender;
use Illuminate\Database\Eloquent\Collection;
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
        $competition->loadMissing('categories.translations', 'categories.ageEligibilityRule.translations', 'categories.genders', 'categories.memberGroups', 'categories.captureDevices');

        return ['categories' => $competition->categories->map(fn (CompetitionCategory $category) => [
            'id' => $category->id,
            'tr' => ['name' => $category->getTranslation('tr', false)?->name],
            'en' => ['name' => $category->getTranslation('en', false)?->name],
            'age_eligibility_rule' => $category->age_eligibility_rule_id,
            'gender_id' => $category->genders->first()?->id,
            'member_group_ids' => $category->memberGroups->pluck('id')->all(),
            'capture_device_ids' => $category->captureDevices->pluck('id')->all(),
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
                'age_eligibility_rule_id' => $payload['age_eligibility_rule'] ?? null,
                'birth_date_restricted' => false,
                'birth_date_from' => null,
                'birth_date_to' => null,
            ]);
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
        }

        $competition->categories()->whereNotIn('id', $keptIds)->delete();
        $competition->unsetRelation('categories');
    }

    public function rules(bool $isDraftSave, Competition $competition): array
    {
        $required = $isDraftSave ? 'nullable' : 'required';
        $categoryId = Rule::exists('competition_categories', 'id')->where('competition_id', $competition->id)->whereNull('deleted_at');
        $activeReference = fn (string $table) => Rule::exists($table, 'id')->where('status', true)->whereNull('deleted_at');

        return [
            'categories' => [$required, 'array', $isDraftSave ? 'max:20' : 'min:1', 'max:20'],
            'categories.*.id' => ['nullable', 'uuid', $categoryId],
            'categories.*.tr.name' => [$required, 'string', 'max:255'],
            'categories.*.en.name' => [$isDraftSave || ! $competition->requiresEnglishContent() ? 'nullable' : 'required', 'nullable', 'string', 'max:255'],
            'categories.*.age_eligibility_rule' => [$required, 'uuid', $activeReference('age_eligibility_rules')],
            'categories.*.gender_id' => [$required, 'uuid', $activeReference('participant_genders')],
            'categories.*.member_group_ids' => [$required, 'array', $isDraftSave ? 'max:30' : 'min:1', 'max:30'],
            'categories.*.member_group_ids.*' => ['uuid', 'distinct', $activeReference('member_groups')],
            'categories.*.capture_device_ids' => [$required, 'array', $isDraftSave ? 'max:30' : 'min:1', 'max:30'],
            'categories.*.capture_device_ids.*' => ['uuid', 'distinct', $activeReference('capture_devices')],
        ];
    }

    public function formData(Competition $competition): array
    {
        $categories = old('categories', $this->data($competition)['categories']);

        return $categories ?: [[
            'id' => null, 'tr' => ['name' => ''], 'en' => ['name' => ''],
            'age_eligibility_rule' => null, 'gender_id' => null,
            'member_group_ids' => [], 'capture_device_ids' => [],
        ]];
    }

    /** @return array{genders: Collection, ageRules: Collection, memberGroups: Collection, captureDevices: Collection} */
    public function options(): array
    {
        return [
            'genders' => ParticipantGender::active()->ordered()->with('translations')->get(),
            'ageRules' => AgeEligibilityRule::active()->ordered()->with('translations')->get(),
            'memberGroups' => MemberGroup::active()->ordered()->with('translations')->get(),
            'captureDevices' => CaptureDevice::active()->ordered()->with('translations')->get(),
        ];
    }
}
