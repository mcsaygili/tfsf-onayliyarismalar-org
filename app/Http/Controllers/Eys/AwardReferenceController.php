<?php

namespace App\Http\Controllers\Eys;

use App\Http\Controllers\Controller;
use App\Models\AwardReference;
use App\Models\AwardReferenceTranslation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AwardReferenceController extends Controller
{
    public function index(Request $request): View
    {
        $locale = app()->getLocale();
        $awards = AwardReference::with('translations')
            ->when($request->filled('name'), fn ($query) => $query->whereHas(
                'translations',
                fn ($translation) => $translation->where('name', 'like', '%'.$request->string('name').'%')
            ))
            ->when($request->filled('kind'), fn ($query) => $query->where('kind', $request->input('kind')))
            ->when(
                $request->filled('status') && in_array($request->input('status'), ['0', '1'], true),
                fn ($query) => $query->where('status', (bool) $request->input('status'))
            )
            ->orderBy('sort_order')
            ->orderBy(AwardReferenceTranslation::select('name')
                ->whereColumn('award_reference_id', 'award_references.id')
                ->where('locale', $locale)
                ->limit(1))
            ->paginate(20)
            ->withQueryString();

        return view('eys.award-references.index', [
            'awards' => $awards,
            'filter' => $request->only(['name', 'kind', 'status']),
        ]);
    }

    public function create(): View
    {
        return view('eys.award-references.create', [
            'award' => new AwardReference(['status' => true]),
            'locales' => array_keys(config('locales.supported')),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $award = AwardReference::create($this->referencePayload($data));
        $award->upsertTranslations($this->translationPayload($data));

        return redirect()->route('eys.award-references.index')->with('status', __('eys.award_reference.created'));
    }

    public function edit(AwardReference $awardReference): View
    {
        $awardReference->load('translations');

        return view('eys.award-references.edit', [
            'award' => $awardReference,
            'locales' => array_keys(config('locales.supported')),
        ]);
    }

    public function update(Request $request, AwardReference $awardReference): RedirectResponse
    {
        $data = $this->validateData($request, $awardReference);
        $payload = $this->referencePayload($data);
        $payload['code'] = $awardReference->is_system ? $awardReference->code : $payload['code'];
        $payload['version'] = $awardReference->version + 1;
        $awardReference->update($payload);
        $awardReference->upsertTranslations($this->translationPayload($data));

        return redirect()->route('eys.award-references.index')->with('status', __('eys.award_reference.updated'));
    }

    public function destroy(AwardReference $awardReference): RedirectResponse
    {
        if ($awardReference->is_system || $awardReference->categoryAwards()->exists()) {
            return back()->with('error', __('eys.reference_in_use'));
        }

        $awardReference->delete();

        return redirect()->route('eys.award-references.index')->with('status', __('eys.award_reference.deleted'));
    }

    private function validateData(Request $request, ?AwardReference $award = null): array
    {
        $codeRule = Rule::unique('award_references', 'code');
        if ($award) {
            $codeRule->ignore($award->id);
        }

        $rules = [
            'code' => ['required', 'string', 'alpha_dash:ascii', 'max:120', $codeRule],
            'kind' => ['required', Rule::in(['award', 'exhibition', 'purchase'])],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'status' => ['required', 'boolean'],
        ];

        foreach (array_keys(config('locales.supported')) as $locale) {
            $rules["$locale.name"] = ['required', 'string', 'max:255'];
            $rules["$locale.description"] = ['nullable', 'string', 'max:1000'];
        }

        return $request->validate($rules);
    }

    private function referencePayload(array $data): array
    {
        return [
            'code' => $data['code'],
            'kind' => $data['kind'],
            'sort_order' => $data['sort_order'] ?: 0,
            'status' => (bool) $data['status'],
        ];
    }

    private function translationPayload(array $data): array
    {
        return collect(array_keys(config('locales.supported')))
            ->mapWithKeys(fn (string $locale) => [$locale => [
                'name' => $data[$locale]['name'],
                'description' => $data[$locale]['description'] ?? null,
            ]])->all();
    }
}
