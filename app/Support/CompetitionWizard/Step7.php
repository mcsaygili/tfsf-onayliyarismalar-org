<?php

namespace App\Support\CompetitionWizard;

use App\Models\AwardReference;
use App\Models\Competition;
use App\Models\CompetitionCategory;
use App\Models\CompetitionCategoryAward;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Adım 7 — Her yarışma kategorisine verilecek ödüllerin atanması. */
class Step7 implements CompetitionStep
{
    public function number(): int
    {
        return 7;
    }

    public function label(): string
    {
        return __('institution.competitions.steps.7.label');
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
        $competition->loadMissing('categories.translations', 'categories.awards.translations');

        return ['categories' => $competition->categories->mapWithKeys(fn (CompetitionCategory $category) => [
            $category->id => ['awards' => $category->awards->map(fn (CompetitionCategoryAward $award) => [
                'id' => $award->id,
                'award_reference_id' => $award->award_reference_id,
                'quantity' => $award->quantity,
                'tr' => [
                    'special_award_text' => $award->getTranslation('tr', false)?->special_award_text,
                    'material_award' => $award->getTranslation('tr', false)?->material_award,
                ],
                'en' => [
                    'special_award_text' => $award->getTranslation('en', false)?->special_award_text,
                    'material_award' => $award->getTranslation('en', false)?->material_award,
                ],
            ])->values()->all()],
        ])->all()];
    }

    public function persist(Competition $competition, array $validated): void
    {
        if (! array_key_exists('categories', $validated)) {
            return;
        }

        $competition->loadMissing('categories');

        foreach ($validated['categories'] as $categoryId => $payload) {
            $category = $competition->categories->firstWhere('id', $categoryId);
            abort_unless($category, 422);
            $keptIds = [];

            foreach ($payload['awards'] ?? [] as $index => $awardData) {
                // Arayüz ilk açıldığında kullanıcıya bir boş ödül satırı gösterir.
                // Taslak kaydında bu görsel başlangıç satırı gerçek kayıt değildir.
                if ($this->isEmptyAward($awardData)) {
                    continue;
                }

                if (blank($awardData['award_reference_id'] ?? null)) {
                    throw ValidationException::withMessages([
                        "categories.$categoryId.awards.$index.award_reference_id" => __('validation.required', [
                            'attribute' => __('institution.competitions.fields.award_reference'),
                        ]),
                    ]);
                }

                $award = filled($awardData['id'] ?? null)
                    ? $category->awards()->whereKey($awardData['id'])->firstOrFail()
                    : new CompetitionCategoryAward(['competition_category_id' => $category->id]);

                $award->fill([
                    'award_reference_id' => $awardData['award_reference_id'],
                    'quantity' => $awardData['quantity'] ?? 1,
                    'sort_order' => ($index + 1) * 10,
                ])->save();
                $keptIds[] = $award->id;

                foreach (['tr', 'en'] as $locale) {
                    $translation = [
                        'special_award_text' => trim((string) data_get($awardData, "$locale.special_award_text", '')) ?: null,
                        'material_award' => trim((string) data_get($awardData, "$locale.material_award", '')) ?: null,
                    ];

                    if (array_filter($translation) === []) {
                        $award->translations()->where('locale', $locale)->delete();
                    } else {
                        $award->translations()->updateOrCreate(['locale' => $locale], $translation);
                    }
                }
            }

            $category->awards()->whereNotIn('id', $keptIds)->delete();
        }
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
                        $fail(__('institution.competitions.validation.category_awards_mismatch'));
                    }
                },
            ],
            'categories.*.awards' => [$required, 'array', $isDraftSave ? 'max:50' : 'min:1', 'max:50'],
            'categories.*.awards.*.id' => [
                'nullable',
                'uuid',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (! $value) {
                        return;
                    }
                    $categoryId = explode('.', $attribute)[1] ?? null;
                    if (! DB::table('competition_category_awards')->where('id', $value)->where('competition_category_id', $categoryId)->exists()) {
                        $fail(__('institution.competitions.validation.category_award_invalid'));
                    }
                },
            ],
            'categories.*.awards.*.award_reference_id' => [$required, 'uuid', Rule::exists('award_references', 'id')->whereNull('deleted_at')],
            'categories.*.awards.*.quantity' => [$required, 'integer', 'min:1', 'max:999'],
            'categories.*.awards.*.tr.special_award_text' => ['nullable', 'string', 'max:255'],
            'categories.*.awards.*.tr.material_award' => ['nullable', 'string', 'max:255'],
            'categories.*.awards.*.en.special_award_text' => ['nullable', 'string', 'max:255'],
            'categories.*.awards.*.en.material_award' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function formData(Competition $competition): array
    {
        $competition->loadMissing('categories');
        $data = old('categories', $this->data($competition)['categories']);

        foreach ($competition->categories as $category) {
            $data[$category->id] ??= ['awards' => []];
            $data[$category->id]['awards'] = array_values($data[$category->id]['awards'] ?: [$this->emptyAward()]);
        }

        return $data;
    }

    /** @return Collection<int, AwardReference> */
    public function options(Competition $competition): Collection
    {
        $assignedIds = CompetitionCategoryAward::query()
            ->whereIn('competition_category_id', $competition->categories()->pluck('id'))
            ->pluck('award_reference_id');

        return AwardReference::query()
            ->where(fn ($query) => $query->where('status', true)->orWhereIn('id', $assignedIds))
            ->whereNull('deleted_at')
            ->with('translations')
            ->ordered()
            ->get();
    }

    private function emptyAward(): array
    {
        return [
            'id' => null,
            'award_reference_id' => '',
            'quantity' => 1,
            'tr' => ['special_award_text' => '', 'material_award' => ''],
            'en' => ['special_award_text' => '', 'material_award' => ''],
        ];
    }

    /** @param array<string, mixed> $award */
    private function isEmptyAward(array $award): bool
    {
        if (filled($award['id'] ?? null) || filled($award['award_reference_id'] ?? null)) {
            return false;
        }

        foreach (['tr', 'en'] as $locale) {
            if (
                filled(data_get($award, "$locale.special_award_text"))
                || filled(data_get($award, "$locale.material_award"))
            ) {
                return false;
            }
        }

        return true;
    }
}
