<?php

namespace App\Http\Controllers\Eys;

use App\Http\Controllers\Controller;
use App\Models\ParticipantApprovalProcess;
use App\Models\ParticipantApprovalProcessTranslation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ParticipantApprovalProcessController extends Controller
{
    public function index(Request $request): View
    {
        $locale = app()->getLocale();

        $processes = ParticipantApprovalProcess::with('translations')
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
                ParticipantApprovalProcessTranslation::select('name')
                    ->whereColumn('participant_approval_process_id', 'participant_approval_processes.id')
                    ->where('locale', $locale)
                    ->limit(1)
            )
            ->paginate(20)
            ->withQueryString();

        return view('eys.participant-approval-processes.index', [
            'processes' => $processes,
            'filter' => [
                'name' => $request->input('name', ''),
                'status' => $request->input('status', ''),
            ],
        ]);
    }

    public function create(): View
    {
        return view('eys.participant-approval-processes.create', [
            'locales' => array_keys(config('locales.supported')),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $process = ParticipantApprovalProcess::create([
            'code' => $data['code'],
            'sort_order' => $data['sort_order'] ?: 0,
            'status' => (bool) $data['status'],
        ]);
        $process->upsertTranslations($this->translationPayload($data));

        return redirect()->route('eys.participant-approval-processes.index')
            ->with('status', __('eys.participant_approval_process.created'));
    }

    public function edit(ParticipantApprovalProcess $participantApprovalProcess): View
    {
        $participantApprovalProcess->load('translations');

        return view('eys.participant-approval-processes.edit', [
            'process' => $participantApprovalProcess,
            'locales' => array_keys(config('locales.supported')),
        ]);
    }

    public function update(Request $request, ParticipantApprovalProcess $participantApprovalProcess): RedirectResponse
    {
        $data = $this->validateData($request, $participantApprovalProcess);
        $participantApprovalProcess->update([
            'code' => $data['code'],
            'sort_order' => $data['sort_order'] ?: 0,
            'status' => (bool) $data['status'],
        ]);
        $participantApprovalProcess->upsertTranslations($this->translationPayload($data));

        return redirect()->route('eys.participant-approval-processes.index')
            ->with('status', __('eys.participant_approval_process.updated'));
    }

    public function destroy(ParticipantApprovalProcess $participantApprovalProcess): RedirectResponse
    {
        $participantApprovalProcess->delete();

        return redirect()->route('eys.participant-approval-processes.index')
            ->with('status', __('eys.participant_approval_process.deleted'));
    }

    /** @return array<string, mixed> */
    private function validateData(Request $request, ?ParticipantApprovalProcess $process = null): array
    {
        $locales = array_keys(config('locales.supported'));
        $defaultLocale = config('locales.default');
        $codeRule = Rule::unique('participant_approval_processes', 'code');

        if ($process) {
            $codeRule->ignore($process->id);
        }

        $rules = [
            'code' => ['required', 'string', 'alpha_dash:ascii', 'max:100', $codeRule],
            'status' => ['required', 'in:0,1'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];

        foreach ($locales as $locale) {
            $rules["{$locale}.name"] = [$locale === $defaultLocale ? 'required' : 'nullable', 'string', 'max:255'];
            $rules["{$locale}.description"] = [$locale === $defaultLocale ? 'required' : 'nullable', 'string', 'max:1000'];
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

            $payload[$locale] = ['name' => $name, 'description' => $description];
        }

        return $payload;
    }
}
