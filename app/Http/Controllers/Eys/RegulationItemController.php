<?php

namespace App\Http\Controllers\Eys;

use App\Http\Controllers\Controller;
use App\Models\RegulationItem;
use App\Models\RegulationSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * EYS yönetici paneli — Şartname Maddesi (referans veri) yönetimi. Her
 * madde zorunlu olarak bir Şartname Bölümü'ne bağlıdır (bkz. CityController
 * ile aynı desen — Bölüm, Ülke'nin karşılığı).
 */
class RegulationItemController extends Controller
{
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
        ]);
    }

    public function update(Request $request, RegulationItem $regulationItem): RedirectResponse
    {
        $data = $this->validateData($request);

        $regulationItem->update([
            'regulation_section_id' => $data['regulation_section_id'],
            'sort_order' => $data['sort_order'] ?: 0,
            'code' => $data['code'] ?? null,
            'status' => (bool) $data['status'],
        ]);

        $regulationItem->upsertTranslations($this->translationPayload($data));

        return redirect()->route('eys.regulation-items.index')->with('status', __('eys.regulation_item.updated'));
    }

    public function destroy(RegulationItem $regulationItem): RedirectResponse
    {
        $regulationItem->delete();

        return redirect()->route('eys.regulation-items.index')->with('status', __('eys.regulation_item.deleted'));
    }

    /** @return array<string, mixed> */
    private function validateData(Request $request): array
    {
        $locales = array_keys(config('locales.supported'));
        $defaultLocale = config('locales.default');

        $rules = [
            'regulation_section_id' => ['required', 'uuid', 'exists:regulation_sections,id'],
            'status' => ['required', 'in:0,1'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'code' => ['nullable', 'string', 'max:50'],
        ];

        foreach ($locales as $locale) {
            $rules["{$locale}.content"] = [
                $locale === $defaultLocale ? 'required' : 'nullable',
                'string',
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
            $content = $data[$locale]['content'] ?? null;

            if (blank($content)) {
                continue;
            }

            $payload[$locale] = ['content' => $content];
        }

        return $payload;
    }
}
