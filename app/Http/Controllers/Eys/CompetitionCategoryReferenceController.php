<?php

namespace App\Http\Controllers\Eys;

use App\Http\Controllers\Controller;
use App\Models\AgeEligibilityRule;
use App\Models\CaptureDevice;
use App\Models\MemberGroup;
use App\Models\ParticipantGender;
use App\Models\ProcessingMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class CompetitionCategoryReferenceController extends Controller
{
    private const TYPES = [
        'participant-genders' => [ParticipantGender::class, 'participant_genders', 'participant_gender', 'eys.participant-genders', false],
        'age-eligibility-rules' => [AgeEligibilityRule::class, 'age_eligibility_rules', 'age_eligibility_rule', 'eys.age-eligibility-rules', true],
        'member-groups' => [MemberGroup::class, 'member_groups', 'member_group', 'eys.member-groups', false],
        'capture-devices' => [CaptureDevice::class, 'capture_devices', 'capture_device', 'eys.capture-devices', false],
        'processing-methods' => [ProcessingMethod::class, 'processing_methods', 'processing_method', 'eys.processing-methods', false],
    ];

    public function index(Request $request): View
    {
        [$model, , $translation, $route, $hasAgeConstraints] = $this->config($request);
        $references = $model::with('translations')
            ->when($request->filled('name'), fn ($query) => $query->whereHas('translations', fn ($q) => $q->where('name', 'like', '%'.$request->input('name').'%')))
            ->when($request->filled('status') && in_array($request->input('status'), ['0', '1'], true), fn ($query) => $query->where('status', (bool) $request->input('status')))
            ->ordered()->paginate(20)->withQueryString();

        return view('eys.competition-category-references.index', compact('references', 'translation', 'route', 'hasAgeConstraints') + [
            'filter' => ['name' => $request->input('name', ''), 'status' => $request->input('status', '')],
        ]);
    }

    public function create(Request $request): View
    {
        [$model, , $translation, $route, $hasAgeConstraints] = $this->config($request);

        return view('eys.competition-category-references.create', compact('translation', 'route') + [
            'reference' => new $model,
            'locales' => array_keys(config('locales.supported')),
            'hasAgeConstraints' => $hasAgeConstraints,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        [$model, $table, $translation, $route, $hasAgeConstraints] = $this->config($request);
        $data = $this->validateData($request, $table, hasAgeConstraints: $hasAgeConstraints);
        $reference = $model::create($this->basePayload($data, $hasAgeConstraints));
        $reference->upsertTranslations($this->translationPayload($data));

        return redirect()->route($route.'.index')->with('status', __("eys.$translation.created"));
    }

    public function edit(Request $request, string $reference): View
    {
        [$model, , $translation, $route, $hasAgeConstraints] = $this->config($request);
        $reference = $model::with('translations')->findOrFail($reference);

        return view('eys.competition-category-references.edit', compact('reference', 'translation', 'route', 'hasAgeConstraints') + ['locales' => array_keys(config('locales.supported'))]);
    }

    public function update(Request $request, string $reference): RedirectResponse
    {
        [$model, $table, $translation, $route, $hasAgeConstraints] = $this->config($request);
        $reference = $model::findOrFail($reference);
        $data = $this->validateData($request, $table, $reference, $hasAgeConstraints);
        $payload = $this->basePayload($data, $hasAgeConstraints);
        $payload['code'] = $reference->is_system ? $reference->code : $payload['code'];
        $payload['version'] = $reference->version + 1;
        $reference->update($payload);
        $reference->upsertTranslations($this->translationPayload($data));

        return redirect()->route($route.'.index')->with('status', __("eys.$translation.updated"));
    }

    public function destroy(Request $request, string $reference): RedirectResponse
    {
        [$model, , $translation, $route] = $this->config($request);
        $referenceModel = $model::findOrFail($reference);

        if ($referenceModel->is_system || $this->isInUse($referenceModel)) {
            return back()->with('error', __('eys.reference_in_use'));
        }

        $referenceModel->delete();

        return redirect()->route($route.'.index')->with('status', __("eys.$translation.deleted"));
    }

    private function config(Request $request): array
    {
        return self::TYPES[$request->route('referenceType')] ?? abort(404);
    }

    private function validateData(Request $request, string $table, ?Model $reference = null, bool $hasAgeConstraints = false): array
    {
        $unique = Rule::unique($table, 'code');
        if ($reference) {
            $unique->ignore($reference->getKey());
        }
        $rules = ['code' => ['required', 'string', 'max:100', 'alpha_dash:ascii', $unique], 'status' => ['required', 'in:0,1'], 'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535']];
        if ($hasAgeConstraints) {
            $rules += [
                'minimum_age' => ['nullable', 'integer', 'min:0', 'max:120'],
                'maximum_age' => ['nullable', 'integer', 'min:0', 'max:120', 'gte:minimum_age'],
                'minimum_inclusive' => ['required', 'boolean'],
                'maximum_inclusive' => ['required', 'boolean'],
            ];
        }
        foreach (array_keys(config('locales.supported')) as $locale) {
            $rules["$locale.name"] = ['required', 'string', 'max:255'];
            $rules["$locale.description"] = ['required', 'string', 'max:1000'];
        }

        return $request->validate($rules);
    }

    private function basePayload(array $data, bool $hasAgeConstraints): array
    {
        $payload = ['code' => $data['code'], 'sort_order' => $data['sort_order'] ?? 0, 'status' => (bool) $data['status']];
        if ($hasAgeConstraints) {
            $payload += [
                'minimum_age' => $data['minimum_age'] ?? null,
                'maximum_age' => $data['maximum_age'] ?? null,
                'minimum_inclusive' => (bool) $data['minimum_inclusive'],
                'maximum_inclusive' => (bool) $data['maximum_inclusive'],
            ];
        }

        return $payload;
    }

    private function translationPayload(array $data): array
    {
        $payload = [];
        foreach (array_keys(config('locales.supported')) as $locale) {
            if (! blank(data_get($data, "$locale.name"))) {
                $payload[$locale] = ['name' => data_get($data, "$locale.name"), 'description' => data_get($data, "$locale.description")];
            }
        }

        return $payload;
    }

    private function isInUse(Model $reference): bool
    {
        return match (true) {
            $reference instanceof ParticipantGender => DB::table('competition_category_gender')->where('participant_gender_id', $reference->getKey())->exists(),
            $reference instanceof AgeEligibilityRule => DB::table('competition_categories')->where('age_eligibility_rule_id', $reference->getKey())->exists(),
            $reference instanceof MemberGroup => DB::table('competition_category_member_group')->where('member_group_id', $reference->getKey())->exists(),
            $reference instanceof CaptureDevice => DB::table('competition_category_capture_device')->where('capture_device_id', $reference->getKey())->exists(),
            $reference instanceof ProcessingMethod => DB::table('competition_category_processing_method')->where('processing_method_id', $reference->getKey())->exists(),
            default => false,
        };
    }
}
