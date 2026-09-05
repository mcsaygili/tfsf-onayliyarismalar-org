<?php

namespace App\Http\Controllers\Juri\Auth;

use App\Http\Controllers\Controller;
use App\Services\PasswordResetLinkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('juri.auth.forgot-password');
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        return app(PasswordResetLinkService::class)->send($request, 'juri');
    }
}
