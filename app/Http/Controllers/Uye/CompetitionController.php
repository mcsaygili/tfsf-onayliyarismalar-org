<?php

namespace App\Http\Controllers\Uye;

use App\Http\Controllers\Controller;
use App\Models\CaptureDevice;
use App\Models\Competition;
use App\Models\CompetitionEntry;
use App\Models\CompetitionSubmission;
use App\Models\CompetitionSubmissionPhoto;
use App\Models\Photo;
use App\Models\ProcessingMethod;
use App\Services\CompetitionEntryMutationPolicy;
use App\Services\CompetitionEntryService;
use App\Services\CompetitionPhaseService;
use App\Services\CompetitionSubmissionPhotoService;
use App\Services\MemberEligibilityService;
use App\Services\MemberScorecardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CompetitionController extends Controller
{
    public function index(Request $request, CompetitionPhaseService $phases): View
    {
        $competitions = Competition::query()
            ->publiclyVisible()
            ->with(['translations', 'institution', 'competitionType.translations', 'categories.translations'])
            ->withCount('categories')
            ->orderBy('application_ends_at')
            ->paginate(12);
        $competitions->getCollection()->each(fn ($competition) => $competition->setAttribute('operational_phase', $phases->phase($competition)));

        return view('uye.competitions.index', compact('competitions'));
    }

    public function show(Request $request, Competition $competition, CompetitionPhaseService $phases, MemberEligibilityService $eligibility): View
    {
        $this->published($competition);
        $competition->load(['translations', 'institution', 'competitionType.translations', 'categories.translations', 'categories.genders.translations', 'categories.ageEligibilityRule.translations', 'categories.memberGroups.translations', 'regulationSnapshots', 'evaluationRounds.results.photo.submission.category.translations', 'evaluationRounds.results.awards.categoryAward.translations', 'evaluationRounds.results.awards.categoryAward.awardReference.translations']);
        $categoryChecks = $competition->categories->mapWithKeys(fn ($category) => [$category->id => $eligibility->forCategory($category, $request->user())]);
        $entry = $competition->entries()->where('user_id', $request->user()->id)->first();
        $resultRound = $competition->evaluationRounds->firstWhere('is_final', true)
            ?? $competition->evaluationRounds->sortByDesc('round_number')->first();

        return view('uye.competitions.show', [
            'competition' => $competition,
            'phase' => $phases->phase($competition),
            'competitionCheck' => $eligibility->forCompetition($competition, $request->user()),
            'categoryChecks' => $categoryChecks,
            'entry' => $entry,
            'resultRound' => $resultRound,
        ]);
    }

    public function entries(Request $request): View
    {
        $entries = $request->user()->competitionEntries()
            ->with(['competition.translations', 'competition.institution', 'submissions.category.translations'])
            ->withCount('submissions')->latest()->paginate(12);

        return view('uye.competitions.entries', compact('entries'));
    }

    public function start(Request $request, Competition $competition, CompetitionEntryService $entries): RedirectResponse
    {
        $this->published($competition);
        $entry = $entries->entryFor($competition, $request->user());

        return redirect()->route('competitions.entry.show', $entry);
    }

    public function entry(Request $request, CompetitionEntry $entry, CompetitionPhaseService $phases, CompetitionEntryMutationPolicy $mutations, MemberScorecardService $scorecards): View
    {
        $this->ownsEntry($request, $entry);
        $entry->load(['competition.translations', 'competition.categories.translations', 'competition.regulationSnapshots', 'submissions.category.translations', 'submissions.photos.captureDevice.translations', 'submissions.approvals']);

        return view('uye.competitions.entry', [
            'entry' => $entry,
            'editable' => $entry->status->isEditable() && $phases->acceptsApplications($entry->competition),
            'submissionMutability' => $entry->submissions->mapWithKeys(fn ($submission) => [$submission->id => $mutations->allows($submission)]),
            'scorecards' => $scorecards->forEntry($entry),
            'portfolioPhotos' => $request->user()->photos()->latest()->get(),
            'captureDevices' => CaptureDevice::active()->ordered()->with('translations')->get(),
            'processingMethods' => ProcessingMethod::active()->ordered()->with('translations')->get(),
        ]);
    }

    public function addCategory(Request $request, CompetitionEntry $entry, CompetitionEntryService $entries): RedirectResponse
    {
        $this->ownsEntry($request, $entry);
        $validated = $request->validate(['category_id' => ['required', 'uuid']]);
        $entries->addCategory($entry, $validated['category_id']);

        return back()->with('status', __('uye.competitions.messages.category_added'));
    }

    public function addPortfolioPhoto(Request $request, CompetitionSubmission $submission, CompetitionSubmissionPhotoService $photos): RedirectResponse
    {
        $this->ownsSubmission($request, $submission);
        $validated = $this->photoRules($request, true);
        $photo = Photo::query()->where('user_id', $request->user()->id)->findOrFail($validated['photo_id']);
        $photos->fromPortfolio($submission, $photo, $validated['capture_device_id'] ?? null, $validated['processing_method_ids'] ?? []);

        return back()->with('status', __('uye.competitions.messages.photo_added'));
    }

    public function uploadPhoto(Request $request, CompetitionSubmission $submission, CompetitionSubmissionPhotoService $photos): RedirectResponse
    {
        $this->ownsSubmission($request, $submission);
        $validated = $this->photoRules($request, false);
        $photos->fromUpload($submission, $request->file('photo'), $validated['capture_device_id'] ?? null, $validated['processing_method_ids'] ?? []);

        return back()->with('status', __('uye.competitions.messages.photo_added'));
    }

    public function removePhoto(Request $request, CompetitionSubmissionPhoto $submissionPhoto, CompetitionSubmissionPhotoService $photos): RedirectResponse
    {
        $submissionPhoto->loadMissing('submission.entry');
        abort_unless($submissionPhoto->submission->entry->user_id === $request->user()->id, 404);
        $photos->remove($submissionPhoto);

        return back()->with('status', __('uye.competitions.messages.photo_removed'));
    }

    public function submit(Request $request, CompetitionEntry $entry, CompetitionEntryService $entries): RedirectResponse
    {
        $this->ownsEntry($request, $entry);
        abort_unless($request->boolean('consent'), 422, __('uye.competitions.errors.consent_required'));
        $entries->submit($entry);

        return redirect()->route('competitions.entry.show', $entry)->with('status', __('uye.competitions.messages.submitted'));
    }

    private function photoRules(Request $request, bool $portfolio): array
    {
        return $request->validate([
            'photo_id' => [$portfolio ? 'required' : 'nullable', 'uuid'],
            'photo' => [$portfolio ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:15360'],
            'capture_device_id' => ['nullable', 'uuid', Rule::exists('capture_devices', 'id')->where('status', true)->whereNull('deleted_at')],
            'processing_method_ids' => ['nullable', 'array'],
            'processing_method_ids.*' => ['uuid', Rule::exists('processing_methods', 'id')->where('status', true)->whereNull('deleted_at')],
        ]);
    }

    private function published(Competition $competition): void
    {
        abort_unless($competition->newQuery()->whereKey($competition->getKey())->publiclyVisible()->exists(), 404);
    }

    private function ownsEntry(Request $request, CompetitionEntry $entry): void
    {
        abort_unless($entry->user_id === $request->user()->id, 404);
    }

    private function ownsSubmission(Request $request, CompetitionSubmission $submission): void
    {
        $submission->loadMissing('entry');
        abort_unless($submission->entry->user_id === $request->user()->id, 404);
    }
}
