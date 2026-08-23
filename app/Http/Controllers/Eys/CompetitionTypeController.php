<?php

namespace App\Http\Controllers\Eys;

use App\Http\Controllers\Controller;
use App\Models\CompetitionType;
use App\Models\CompetitionTypeTranslation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CompetitionTypeController extends Controller
{
    public function index(Request $request): View
    {
        $locale = app()->getLocale();

        $competitionTypes = CompetitionType::with('translations')
            ->when($request->filled('name'), function ($query) use ($request) {
                $name = $request->string('name');
                $query->whereHas('translations', fn ($translation) => $translation->where('name', 'like', "%{$name}%"));
            })
            ->when(
                $request->filled('status') && in_array($request->input('status'), ['0', '1'], true),
                fn ($query) => $query->where('status', (bool) $request->input('status'))
            )
            ->orderBy('sort_order')
            ->orderBy(
                CompetitionTypeTranslation::select('name')
                    ->whereColumn('competition_type_id', 'competition_types.id')
                    ->where('locale', $locale)
                    ->limit(1)
            )
            ->paginate(20)
            ->withQueryString();

        return view('eys.competition-types.index', [
            'competitionTypes' => $competitionTypes,
            'filter' => [
                'name' => $request->input('name', ''),
                'status' => $request->input('status', ''),
            ],
        ]);
    }

    public function create(): View
    {
        return view('eys.competition-types.create', [
            'locales' => array_keys(config('locales.supported')),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        $competitionType = CompetitionType::create([
            'code' => $data['code'],
            'sort_order' => $data['sort_order'] ?: 0,
            'status' => (bool) $data['status'],
        ]);

        $competitionType->upsertTranslations($this->translationPayload($data));

        return redirect()->route('eys.competition-types.index')->with('status', __('eys.competition_type.created'));
    }

    public function edit(CompetitionType $competitionType): View
    {
        $competitionType->load('translations');

        return view('eys.competition-types.edit', [
            'competitionType' => $competitionType,
            'locales' => array_keys(config('locales.supported')),
        ]);
    }

    public function update(Request $request, CompetitionType $competitionType): RedirectResponse
    {
        $data = $this->validateData($request, $competitionType);

        $competitionType->update([
            'code' => $data['code'],
            'sort_order' => $data['sort_order'] ?: 0,
            'status' => (bool) $data['status'],
        ]);

        $competitionType->upsertTranslations($this->translationPayload($data));

        return redirect()->route('eys.competition-types.index')->with('status', __('eys.competition_type.updated'));
    }

    public function destroy(CompetitionType $competitionType): RedirectResponse
    {
        $competitionType->delete();

        return redirect()->route('eys.competition-types.index')->with('status', __('eys.competition_type.deleted'));
    }

    /** @return array<string, mixed> */
    private function validateData(Request $request, ?CompetitionType $competitionType = null): array
    {
        $locales = array_keys(config('locales.supported'));
        $defaultLocale = config('locales.default');
        $codeRule = Rule::unique('competition_types', 'code');

        if ($competitionType) {
            $codeRule->ignore($competitionType->id);
        }

        $rules = [
            'code' => ['required', 'string', 'alpha_dash:ascii', 'max:100', $codeRule],
            'status' => ['required', 'in:0,1'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];

        foreach ($locales as $locale) {
            $rules["{$locale}.name"] = [
                $locale === $defaultLocale ? 'required' : 'nullable',
                'string',
                'max:255',
            ];
            $rules["{$locale}.description"] = [
                $locale === $defaultLocale ? 'required' : 'nullable',
                'string',
                'max:1000',
            ];
        }

        return $request->validate($rules);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, array<string, mixed>>
     */
    private function translationPayload(array $data): array
    {
        $payload = [];

        foreach (array_keys(config('locales.supported')) as $locale) {
            $name = $data[$locale]['name'] ?? null;
            $description = $data[$locale]['description'] ?? null;

            if (blank($name) && blank($description)) {
                continue;
            }

            $payload[$locale] = [
                'name' => $name,
                'description' => $description,
            ];
        }

        return $payload;
    }
}
