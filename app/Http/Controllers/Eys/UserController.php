<?php

namespace App\Http\Controllers\Eys;

use App\Http\Controllers\Controller;
use App\Models\EysUser;
use App\Services\PanelSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * EYS'de herkese açık bir kayıt akışı yok — yeni kullanıcılar sadece
 * mevcut bir EYS kullanıcısı tarafından buradan oluşturuluyor. Header
 * dropdown'daki "Hesabım" de kendi kaydını düzenlemek için edit()'e
 * yönleniyor (bkz. app-layout).
 */
class UserController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', EysUser::class);
        $search = trim((string) $request->query('q', ''));
        $status = $request->query('status', '');

        $users = EysUser::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when(in_array($status, ['0', '1'], true), fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('eys.users.index', [
            'users' => $users,
            'filter' => [
                'q' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', EysUser::class);

        return view('eys.users.create');
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', EysUser::class);
        $validated = $request->validate([
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.EysUser::class],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        EysUser::create([
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'first_name' => $validated['first_name'] ?? null,
            'last_name' => $validated['last_name'] ?? null,
            'phone' => $validated['phone'] ?? null,
        ]);

        return redirect()->route(Gate::allows('viewAny', EysUser::class) ? 'eys.users.index' : 'eys.dashboard')->with('status', __('eys.users.created'));
    }

    public function edit(EysUser $user): View
    {
        Gate::authorize('update', $user);

        return view('eys.users.edit', [
            'user' => $user,
            'self' => request()->user('eys')->is($user),
            'canManageIdentity' => Gate::allows('manageIdentity', EysUser::class),
            'backRoute' => Gate::allows('viewAny', EysUser::class) ? 'eys.users.index' : 'eys.dashboard',
        ]);
    }

    public function update(Request $request, EysUser $user): RedirectResponse
    {
        Gate::authorize('update', $user);
        $self = $request->user('eys')->is($user);
        $manage = Gate::allows('manageIdentity', EysUser::class);
        // Ordinary profile editing must not permit account takeover via email.
        abort_if(! $self && ! $manage && $request->input('email') !== $user->email, 403);
        abort_if(! $manage && $request->has('status'), 403);
        $validated = $request->validate([
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(EysUser::class)->ignore($user->id)],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => [$manage ? 'required' : 'prohibited', 'boolean'],
            'current_password' => [Rule::requiredIf($self && $request->input('email') !== $user->email), 'nullable', 'current_password:eys'],
        ]);

        $confirmedPassword = $validated['current_password'] ?? null;
        unset($validated['current_password']);
        $emailChanged = false;
        $user = $user->getConnection()->transaction(function () use ($user, $validated, $self, $manage, $confirmedPassword, &$emailChanged) {
            $current = $user->newQuery()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $emailChanged = $current->email !== $validated['email'];
            abort_if(! $self && ! $manage && $emailChanged, 403);
            if ($self && $emailChanged && (! is_string($confirmedPassword) || ! Hash::check($confirmedPassword, $current->password))) {
                throw ValidationException::withMessages(['current_password' => __('auth.password')]);
            }
            $current->fill($validated);
            if ($emailChanged) {
                $current->forceFill(['remember_token' => Str::random(60)]);
            }
            $current->save();

            return $current;
        }, 3);
        if ($self && $emailChanged) {
            app(PanelSession::class)->stamp($request->session(), $user, 'eys');
        }

        return redirect()->route(Gate::allows('viewAny', EysUser::class) ? 'eys.users.index' : 'eys.users.edit',
            Gate::allows('viewAny', EysUser::class) ? [] : ['user' => $user])->with('status', __('eys.users.updated'));
    }
}
