<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Models\CompetitionRegistration;
use App\Models\InstitutionStaff;
use App\Models\RegistrationExceptionGrant;
use App\Models\Temsilci;
use App\Models\User;
use App\Services\CompetitionRegistrationService;
use App\Services\RegistrationExceptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RegistrationExceptionController extends Controller
{
    public function permissions(Request $request, Competition $competition)
    {
        Gate::forUser($request->user())->authorize('manageRegistrationExceptions', $competition);
        $grants = RegistrationExceptionGrant::where('competition_id', $competition->id)->get();
        $actors = $competition->registration_reviewer === 'institution'
            ? InstitutionStaff::where('institution_id', $competition->institution_id)->where('status', true)->get()
            : Temsilci::whereKey($competition->representative_id)->where('status', true)->get();
        if ($competition->registration_reviewer === 'institution' && $competition->secretariat_id) {
            if ($secretariat = InstitutionStaff::whereKey($competition->secretariat_id)->where('account_kind', 'secretariat')->where('status', true)->first()) {
                $actors->push($secretariat);
            }
        }
        // Retain out-of-scope/inactive recipients in the screen so old grants can be revoked.
        foreach ($grants as $grant) {
            $class = $this->actorClass($grant->actor_type === InstitutionStaff::class ? 'institution' : 'representative');
            if ($actor = $class::find($grant->actor_id)) {
                $actors->push($actor);
            }
        }
        $actors = $actors->unique(fn ($actor) => $actor::class.':'.$actor->id);

        return response()->view('registrations.permissions', compact('competition', 'grants', 'actors'))->header('Cache-Control', 'private, no-store');
    }

    public function grant(Request $request, Competition $competition, RegistrationExceptionService $service)
    {
        Gate::forUser($request->user())->authorize('manageRegistrationExceptions', $competition);
        $data = $request->validate([
            'actor_type' => ['required', 'in:institution,representative'], 'actor_id' => ['required', 'uuid'],
            'version' => ['required', 'integer', 'min:0'], 'active' => ['required', 'boolean'], 'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ]);
        $actor = $this->actorClass($data['actor_type'])::findOrFail($data['actor_id']);
        $service->setGrant($competition, $request->user(), $actor, $data['version'], (bool) $data['active'], $data['reason']);

        return redirect()->route('eys.competitions.registration-permissions', $competition)->with('status', __('registration.exception_permission_saved'));
    }

    public function create(Request $request, Competition $competition, RegistrationExceptionService $service)
    {
        $grant = $service->authorize($competition, $request->user());
        $member = is_string($request->old('user_id')) ? User::find($request->old('user_id')) : null;
        $registration = $member ? CompetitionRegistration::where('competition_id', $competition->id)->where('user_id', $member->id)->with(['documents' => fn ($q) => $q->where('is_current', true)])->first() : null;

        return $this->form($request, $competition, $grant, $member, $registration);
    }

    public function lookup(Request $request, Competition $competition, RegistrationExceptionService $service)
    {
        $grant = $service->authorize($competition, $request->user());
        try {
            $data = $request->validate(['email' => ['required', 'email', 'max:255']]);
        } catch (ValidationException $e) {
            throw $e->redirectTo(route($this->panel($request).'.registrations.direct.create', $competition));
        }
        $member = User::where('email', mb_strtolower(trim($data['email'])))->first();
        if (! $member) {
            throw ValidationException::withMessages(['email' => __('registration.exception_member_missing')])->redirectTo(route($this->panel($request).'.registrations.direct.create', $competition));
        }
        $registration = CompetitionRegistration::where('competition_id', $competition->id)->where('user_id', $member->id)->with(['documents' => fn ($q) => $q->where('is_current', true)])->first();

        return $this->form($request, $competition, $grant, $member, $registration);
    }

    public function store(Request $request, Competition $competition, RegistrationExceptionService $exceptions, CompetitionRegistrationService $service)
    {
        $exceptions->authorize($competition, $request->user());
        try {
            $data = $request->validate([
                'user_id' => ['required', 'uuid'], 'version' => ['required', 'integer', 'min:0'],
                'grant_version' => ['required', 'integer', 'min:1'], 'waive_documents' => ['required', 'boolean'],
                'reason' => ['required', 'string', 'min:10', 'max:2000'],
            ]);
            $registration = $service->approveDirectly($competition, $request->user(), User::findOrFail($data['user_id']), $data['version'], $data['grant_version'], (bool) $data['waive_documents'], $data['reason']);
        } catch (ValidationException $e) {
            throw $e->redirectTo(route($this->panel($request).'.registrations.direct.create', $competition));
        }

        return redirect()->route($this->panel($request).'.registrations.show', $registration)->with('status', __('registration.exception_saved'));
    }

    private function form(Request $request, Competition $competition, RegistrationExceptionGrant $grant, ?User $member = null, ?CompetitionRegistration $registration = null)
    {
        $panel = $this->panel($request);

        return response()->view('registrations.direct', compact('competition', 'grant', 'member', 'registration', 'panel'))->header('Cache-Control', 'private, no-store');
    }

    private function panel(Request $request): string
    {
        return $request->user() instanceof InstitutionStaff ? 'institution' : 'temsilci';
    }

    private function actorClass(string $type): string
    {
        return $type === 'institution' ? InstitutionStaff::class : Temsilci::class;
    }
}
