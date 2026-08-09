<?php

namespace App\Http\Controllers\Temsilci;

use App\Http\Controllers\Controller;
use App\Models\Temsilci;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('temsilci.profile.edit', [
            'temsilci' => Auth::guard('temsilci')->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $temsilci = Auth::guard('temsilci')->user();

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(Temsilci::class)->ignore($temsilci->id)],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $temsilci->update($validated);

        return redirect()->route('temsilci.profile.edit')->with('status', __('temsilci.profile.updated'));
    }
}
