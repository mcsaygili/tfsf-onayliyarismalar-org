<?php

namespace App\Http\Controllers\Eys;

use App\Http\Controllers\Controller;
use App\Models\EvaluationCriterion;
use App\Models\EvaluationCriterionTranslation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EvaluationCriterionController extends Controller
{
    public function index(Request $request): View
    {
        $locale = app()->getLocale();
        $status = $request->input('status', '1');
        $criteria = EvaluationCriterion::with('translations')
            ->when($request->filled('name'), fn ($query) => $query->whereHas(
                'translations',
                fn ($translation) => $translation->where('name', 'like', '%'.$request->string('name').'%')
            ))
            ->when(in_array($status, ['0', '1'], true), fn ($query) => $query->where('status', (bool) $status))
            ->orderBy('sort_order')
            ->orderBy(EvaluationCriterionTranslation::select('name')
                ->whereColumn('evaluation_criterion_id', 'evaluation_criteria.id')
                ->where('locale', $locale)
                ->limit(1))
            ->paginate(20)
            ->withQueryString();

        return view('eys.evaluation-criteria.index', [
            'criteria' => $criteria,
            'filter' => ['name' => $request->input('name', ''), 'status' => $status],
        ]);
    }

    public function create(): View
    {
        return view('eys.evaluation-criteria.create', [
            'criterion' => new EvaluationCriterion([
                'default_min_score' => 3,
                'default_max_score' => 9,
                'default_weight' => 1,
                'status' => true,
            ]),
            'locales' => array_keys(config('locales.supported')),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $criterion = EvaluationCriterion::create($this->referencePayload($data));
        $criterion->upsertTranslations($this->translationPayload($data));

        return redirect()->route('eys.evaluation-criteria.index')->with('status', __('eys.evaluation_criterion.created'));
    }

    public function edit(EvaluationCriterion $evaluationCriterion): View
    {
        $evaluationCriterion->load('translations');

        return view('eys.evaluation-criteria.edit', [
            'criterion' => $evaluationCriterion,
            'locales' => array_keys(config('locales.supported')),
        ]);
    }

    public function update(Request $request, EvaluationCriterion $evaluationCriterion): RedirectResponse
    {
        $data = $this->validateData($request, $evaluationCriterion);
        $payload = $this->referencePayload($data);
        $payload['code'] = $evaluationCriterion->is_system ? $evaluationCriterion->code : $payload['code'];
        $payload['version'] = $evaluationCriterion->version + 1;
        $evaluationCriterion->update($payload);
        $evaluationCriterion->upsertTranslations($this->translationPayload($data));

        return redirect()->route('eys.evaluation-criteria.index')->with('status', __('eys.evaluation_criterion.updated'));
    }

    public function destroy(EvaluationCriterion $evaluationCriterion): RedirectResponse
    {
        if ($evaluationCriterion->is_system || $evaluationCriterion->categoryCriteria()->exists()) {
            return back()->with('error', __('eys.reference_in_use'));
        }

        $evaluationCriterion->delete();

        return redirect()->route('eys.evaluation-criteria.index')->with('status', __('eys.evaluation_criterion.deleted'));
    }

    private function validateData(Request $request, ?EvaluationCriterion $criterion = null): array
    {
        $codeRule = Rule::unique('evaluation_criteria', 'code');
        if ($criterion) {
            $codeRule->ignore($criterion->id);
        }

        $rules = [
            'code' => ['required', 'string', 'alpha_dash:ascii', 'max:120', $codeRule],
            'default_min_score' => ['required', 'integer', 'min:0', 'max:9999'],
            'default_max_score' => ['required', 'integer', 'gt:default_min_score', 'max:10000'],
            'default_weight' => ['required', 'numeric', 'gt:0', 'max:999.99'],
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
            'default_min_score' => $data['default_min_score'],
            'default_max_score' => $data['default_max_score'],
            'default_weight' => $data['default_weight'],
            'sort_order' => ($data['sort_order'] ?? 0) ?: 0,
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
