<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Models\CompetitionRegistration;
use App\Models\CompetitionRegistrationDocument;
use App\Models\InstitutionStaff;
use App\Models\RegistrationExceptionGrant;
use App\Services\CompetitionRegistrationService;
use App\Services\RegistrationExceptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class CompetitionRegistrationController extends Controller
{
    public function show(Request $request, Competition $competition)
    {
        abort_unless($competition->registration_required && $competition->newQuery()->whereKey($competition->id)->publiclyVisible()->exists(), 404);
        $registration = CompetitionRegistration::where('competition_id', $competition->id)->where('user_id', $request->user()->id)->with('documents')->first();

        return view('uye.competitions.registration', compact('competition', 'registration'));
    }

    public function register(Request $request, Competition $competition, CompetitionRegistrationService $service)
    {
        $service->register($competition, $request->user());

        return redirect()->route('competitions.registration.show', $competition)->with('status', __('registration.created'));
    }

    public function upload(Request $request, CompetitionRegistration $registration, CompetitionRegistrationService $service)
    {
        abort_unless($registration->user_id === $request->user()->id, 404);
        $data = $request->validate(['version' => ['required', 'integer', 'min:1'], 'slot' => ['required', 'integer', 'between:1,3'], 'document' => ['required', 'file', 'max:10240']]);
        $service->upload($registration, $request->user(), $data['version'], $data['slot'], $request->file('document'));

        return back()->with('status', __('registration.saved'));
    }

    public function remove(Request $request, CompetitionRegistrationDocument $document, CompetitionRegistrationService $service)
    {
        abort_unless($document->registration->user_id === $request->user()->id, 404);
        $data = $request->validate(['version' => ['required', 'integer', 'min:1']]);
        $service->removeDocument($document, $request->user(), $data['version']);

        return back()->with('status', __('registration.saved'));
    }

    public function submit(Request $request, CompetitionRegistration $registration, CompetitionRegistrationService $service)
    {
        abort_unless($registration->user_id === $request->user()->id, 404);
        $data = $request->validate(['version' => ['required', 'integer', 'min:1']]);
        $service->submit($registration, $request->user(), $data['version']);

        return back()->with('status', __('registration.submitted'));
    }

    public function index(Request $request)
    {
        $actor = $request->user();
        $institution = $actor instanceof InstitutionStaff;
        $registrations = CompetitionRegistration::whereNotNull('submitted_at')->where('reviewer', $institution ? 'institution' : 'representative')
            ->whereHas('competition', fn ($q) => $institution ? app(\App\Services\InstitutionCompetitionAccess::class)->scope($q, $actor) : $q->where('representative_id', $actor->id))
            ->with(['user', 'competition.translations'])->latest('submitted_at')->paginate(20);
        $panel = $institution ? 'institution' : 'temsilci';

        $directCompetitions = RegistrationExceptionGrant::where('actor_type', $actor::class)->where('actor_id', $actor->id)->where('active', true)
            ->with('competition.translations')->get()->pluck('competition')
            ->filter(fn ($competition) => $competition && app(RegistrationExceptionService::class)->inScope($competition, $actor));

        return view('registrations.index', compact('registrations', 'panel', 'directCompetitions'));
    }

    public function review(Request $request, CompetitionRegistration $registration)
    {
        Gate::forUser($request->user())->authorize('review', $registration);
        $panel = $request->user() instanceof InstitutionStaff ? 'institution' : 'temsilci';
        $registration->load(['documents', 'user', 'competition.translations', 'events']);

        return view('registrations.review', compact('registration', 'panel'));
    }

    public function decide(Request $request, CompetitionRegistration $registration, CompetitionRegistrationService $service)
    {
        Gate::forUser($request->user())->authorize('review', $registration);
        $data = $request->validate(['version' => ['required', 'integer', 'min:1'], 'decision' => ['required', 'in:approved,rejected,changes_requested'], 'note' => ['nullable', 'string', 'max:2000']]);
        $service->decide($registration, $request->user(), $data['version'], $data['decision'], $data['note'] ?? null);

        return back()->with('status', __('registration.decision_saved'));
    }

    public function download(Request $request, CompetitionRegistrationDocument $document)
    {
        Gate::forUser($request->user())->authorize('view', $document->registration);
        abort_unless($document->isTrusted(), 404);
        $disk = Storage::disk('local');
        abort_unless($disk->exists($document->disk_path), 404);
        $stream = $disk->readStream($document->disk_path);
        abort_unless(is_resource($stream), 404);
        $hash = hash_init('sha256');
        hash_update_stream($hash, $stream);
        if (! hash_equals($document->sha256, hash_final($hash))) {
            fclose($stream);
            abort(404);
        }
        rewind($stream);

        return response()->streamDownload(function () use ($stream) {
            try {
                fpassthru($stream);
            } finally {
                fclose($stream);
            }
        }, 'registration-'.$document->registration->number.'-document-'.$document->slot.'-v'.$document->version.'.pdf', [
            'Content-Type' => 'application/octet-stream', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store', 'Content-Security-Policy' => 'sandbox',
        ]);
    }
}
