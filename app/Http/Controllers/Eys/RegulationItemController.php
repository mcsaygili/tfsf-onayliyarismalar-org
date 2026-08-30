<?php

namespace App\Http\Controllers\Eys;

use App\Http\Controllers\Controller;
use App\Models\RegulationItem;
use App\Models\RegulationSection;
use App\Support\CompetitionRegulations\CompetitionRegulationDefinitionRegistry;
use App\Support\CompetitionRegulations\RegulationTemplateRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * EYS yönetici paneli — Şartname Maddesi (referans veri) yönetimi. Her
 * madde zorunlu olarak bir Şartname Bölümü'ne bağlıdır (bkz. CityController
 * ile aynı desen — Bölüm, Ülke'nin karşılığı).
 */
class RegulationItemController extends Controller
{
    public function __construct(
        private readonly CompetitionRegulationDefinitionRegistry $definitions,
        private readonly RegulationTemplateRenderer $templateRenderer,
    ) {}

    public function index(Request $request): View
    {
        $items = RegulationItem::with(['translations', 'section.translations'])
            ->when($request->filled('content'), function ($q) use ($request) {
                $content = $request->string('content');
                $q->whereHas('translations', fn ($t) => $t->where('content', 'like', "%{$content}%"));
            })
            ->when($request->filled('regulation_section_id'), fn ($q) => $q->where('regulation_section_id', $request->input('regulation_section_id')))
            ->when($request->filled('status') && in_array($request->input('status'), ['0', '1'], true),
                fn ($q) => $q->where('status', (bool) $request->input('status')))
            ->orderBy('regulation_section_id')
            ->orderBy('sort_order')
            ->paginate(20)
            ->withQueryString();

        return view('eys.regulation-items.index', [
            'items' => $items,
            'sectionOptions' => RegulationSection::ordered()->with('translations')->get(),
            'filter' => [
                'content' => $request->input('content', ''),
                'regulation_section_id' => $request->input('regulation_section_id', ''),
                'status' => $request->input('status', ''),
            ],
        ]);
    }

    public function create(): View
    {
        return view('eys.regulation-items.create', [
            'sections' => RegulationSection::active()->ordered()->with('translations')->get(),
            'locales' => array_keys(config('locales.supported')),
            ...$this->definitionViewData(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        $item = RegulationItem::create([
            'regulation_section_id' => $data['regulation_section_id'],
            'sort_order' => $data['sort_order'] ?: 0,
            'code' => $data['code'] ?? null,
            'status' => (bool) $data['status'],
            'content_type' => $data['content_type'],
            'render_scope' => $data['render_scope'],
            'source_key' => $data['source_key'] ?? null,
            'conditions' => filled($data['conditions'] ?? null) ? json_decode($data['conditions'], true, 512, JSON_THROW_ON_ERROR) : null,
            'is_required' => (bool) $data['is_required'],
        ]);

        $item->upsertTranslations($this->translationPayload($data));

        return redirect()->route('eys.regulation-items.index')->with('status', __('eys.regulation_item.created'));
    }

    public function edit(RegulationItem $regulationItem): View
    {
        $regulationItem->load('translations');

        return view('eys.regulation-items.edit', [
            'item' => $regulationItem,
            'sections' => RegulationSection::active()->ordered()->with('translations')->get(),
            'locales' => array_keys(config('locales.supported')),
            ...$this->definitionViewData(),
        ]);
    }

    public function update(Request $request, RegulationItem $regulationItem): RedirectResponse
    {
        $data = $this->validateData($request);

        $regulationItem->update([
            'regulation_section_id' => $data['regulation_section_id'],
            'sort_order' => $data['sort_order'] ?: 0,
            'code' => $regulationItem->is_system ? $regulationItem->code : ($data['code'] ?? null),
            'status' => (bool) $data['status'],
            'content_type' => $data['content_type'],
            'render_scope' => $data['render_scope'],
            'source_key' => $data['source_key'] ?? null,
            'conditions' => filled($data['conditions'] ?? null) ? json_decode($data['conditions'], true, 512, JSON_THROW_ON_ERROR) : null,
            'is_required' => (bool) $data['is_required'],
            'version' => $regulationItem->version + 1,
        ]);

        $regulationItem->upsertTranslations($this->translationPayload($data));

        return redirect()->route('eys.regulation-items.index')->with('status', __('eys.regulation_item.updated'));
    }

    public function destroy(RegulationItem $regulationItem): RedirectResponse
    {
        if ($regulationItem->is_system || $regulationItem->competitionRegulationInputs()->exists()) {
            return back()->with('error', __('eys.reference_in_use'));
        }

        $regulationItem->delete();

        return redirect()->route('eys.regulation-items.index')->with('status', __('eys.regulation_item.deleted'));
    }

    /** @return array<string, mixed> */
    private function validateData(Request $request): array
    {
        $request->merge([
            'render_scope' => $request->input('render_scope', 'once'),
            'is_required' => $request->input('is_required', '1'),
        ]);
        $locales = array_keys(config('locales.supported'));

        $rules = [
            'regulation_section_id' => ['required', 'uuid', 'exists:regulation_sections,id'],
            'status' => ['required', 'in:0,1'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('regulation_items', 'code')->ignore($request->route('regulationItem')?->id)],
            'content_type' => ['required', 'in:fixed,template,source,institution_input'],
            'render_scope' => ['required', Rule::in(array_keys($this->definitions->renderScopes()))],
            'source_key' => ['nullable', 'required_if:content_type,source', 'string', 'max:100'],
            'conditions' => ['nullable', 'json'],
            'is_required' => ['required', 'boolean'],
        ];

        foreach ($locales as $locale) {
            $rules["{$locale}.content"] = [
                'required',
                'string',
            ];
        }

        $data = $request->validate($rules);
        $this->validateConditions($data['conditions'] ?? null);

        if ($data['content_type'] === 'template') {
            $templateErrors = [];
            foreach ($locales as $locale) {
                $errors = $this->templateRenderer->validate($data[$locale]['content'], $data['render_scope']);
                if ($errors !== []) {
                    $templateErrors["$locale.content"] = $errors;
                }
            }
            if ($templateErrors !== []) {
                throw ValidationException::withMessages($templateErrors);
            }
        }

        return $data;
    }

    private function validateConditions(?string $json): void
    {
        if (blank($json)) {
            return;
        }

        $conditions = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $rules = $conditions['all'] ?? null;
        $valid = is_array($rules) && collect($rules)->every(function ($rule): bool {
            return is_array($rule)
                && in_array($rule['field'] ?? null, $this->definitions->allowedConditionFields(), true)
                && in_array($rule['operator'] ?? null, array_keys($this->definitions->operators()), true)
                && (in_array($rule['operator'], ['exists', 'not_empty'], true) || filled($rule['value'] ?? null));
        });

        if (! $valid) {
            throw ValidationException::withMessages(['conditions' => __('eys.regulation_item.conditions_invalid')]);
        }
    }

    /** @return array<string, mixed> */
    private function definitionViewData(): array
    {
        return [
            'renderScopes' => $this->definitions->renderScopes(),
            'conditionFields' => $this->definitions->conditionFields(),
            'conditionOperators' => $this->definitions->operators(),
            'templateTokens' => collect($this->definitions->renderScopes())
                ->mapWithKeys(fn ($_label, string $scope) => [$scope => $this->definitions->tokensForScope($scope)])
                ->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, array<string, mixed>>
     */
    private function translationPayload(array $data): array
    {
        $payload = [];

        foreach (array_keys(config('locales.supported')) as $locale) {
            $content = $data[$locale]['content'] ?? null;

            if (blank($content)) {
                continue;
            }

            $payload[$locale] = ['content' => $content];
        }

        return $payload;
    }
}
