<?php

namespace App\Http\Controllers\Juri;

use App\Http\Controllers\Controller;
use App\Models\Juri;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('juri.profile.edit', [
            'juri' => Auth::guard('juri')->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $juri = Auth::guard('juri')->user();

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(Juri::class)->ignore($juri->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'tckimlikno' => ['nullable', 'string', 'size:11'],
        ]);

        $juri->update($validated);

        return redirect()->route('juri.profile.edit')->with('status', __('juri.profile.updated'));
    }
}
