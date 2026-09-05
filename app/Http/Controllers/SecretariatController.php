<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Models\InstitutionStaff;
use App\Services\InstitutionCompetitionAccess;
use App\Services\PanelSession;
use App\Services\SecretariatService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class SecretariatController extends Controller
{
    public function index(Request $request, SecretariatService $service)
    {
        $service->authorize($request->user());
        $accounts = InstitutionStaff::where('account_kind', 'secretariat')->orderBy('email')->paginate(20);

        return view('secretariat.accounts', compact('accounts'));
    }

    public function create(Request $request, SecretariatService $service)
    {
        $service->authorize($request->user());

        return view('secretariat.account', ['account' => null, 'context' => null]);
    }

    public function store(Request $request, SecretariatService $service)
    {
        $service->authorize($request->user());
        $data = $request->validate($this->rules() + ['password' => ['required', 'confirmed', Password::defaults()]]);
        $service->create($request->user(), $data);

        return redirect()->route('eys.secretariats.index')->with('status', __('secretariat.saved'));
    }

    public function edit(Request $request, InstitutionStaff $account, SecretariatService $service)
    {
        $service->authorize($request->user());
        abort_unless($account->isSecretariat(), 404);

        return view('secretariat.account', ['account' => $account, 'context' => $service->context($account)]);
    }

    public function update(Request $request, InstitutionStaff $account, SecretariatService $service)
    {
        $service->authorize($request->user());
        $service->update($request->user(), $account, $request->validate($this->rules($account) + ['context' => ['required', 'string', 'size:64']]));

        return redirect()->route('eys.secretariats.index')->with('status', __('secretariat.saved'));
    }

    public function assignment(Request $request, Competition $competition, SecretariatService $service)
    {
        $service->authorize($request->user());
        $accounts = InstitutionStaff::where('account_kind', 'secretariat')->where('status', true)->whereNotNull('email_verified_at')->orderBy('email')->get();

        return view('secretariat.assignment', compact('competition', 'accounts'));
    }

    public function assign(Request $request, Competition $competition, SecretariatService $service)
    {
        $service->authorize($request->user());
        $data = $request->validate(['account_id' => ['nullable', 'uuid'], 'version' => ['required', 'integer', 'min:0'], 'reason' => ['required', 'string', 'min:10', 'max:2000']]);
        $service->assign($competition, $request->user(), $data['account_id'] ?? null, $data['version'], $data['reason']);

        return back()->with('status', __('secretariat.saved'));
    }

    public function dashboard(Request $request, InstitutionCompetitionAccess $access)
    {
        $actor = $request->user('institution');
        abort_unless($actor->isSecretariat(), 404);
        $competitions = $access->scope(Competition::query(), $actor)->with(['translations', 'institution'])->withCount('entries')->latest()->paginate(15);

        return view('secretariat.dashboard', compact('competitions'));
    }

    public function profile(Request $request, SecretariatService $service)
    {
        $account = $request->user('institution');
        abort_unless($account->isSecretariat(), 404);

        return view('secretariat.profile', ['account' => $account, 'context' => $service->context($account)]);
    }

    public function updateProfile(Request $request, SecretariatService $service)
    {
        $account = $request->user('institution');
        abort_unless($account->isSecretariat(), 404);
        $data = $request->validate(['first_name' => ['required', 'string', 'max:255'], 'last_name' => ['required', 'string', 'max:255'], 'phone' => ['nullable', 'string', 'max:50'], 'context' => ['required', 'string', 'size:64']]);
        $service->profile($account, $data);
        app(PanelSession::class)->stamp($request->session(), $account->fresh(), 'institution');

        return back()->with('status', __('secretariat.saved'));
    }

    private function rules(?InstitutionStaff $account = null): array
    {
        return ['email' => ['required', 'email', 'lowercase', 'max:255', Rule::unique(InstitutionStaff::class)->ignore($account?->id)],
            'first_name' => ['required', 'string', 'max:255'], 'last_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'], 'status' => ['required', 'boolean']];
    }
}
