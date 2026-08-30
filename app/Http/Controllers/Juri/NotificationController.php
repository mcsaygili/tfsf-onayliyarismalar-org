<?php

namespace App\Http\Controllers\Juri;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(): View
    {
        return view('juri.notifications.index', ['notifications' => Auth::guard('juri')->user()->notifications()->paginate(20)]);
    }

    public function show(string $notification): RedirectResponse
    {
        $item = Auth::guard('juri')->user()->notifications()->findOrFail($notification);
        $item->markAsRead();

        return redirect()->route($item->data['route_name'] ?? 'juri.dashboard', $item->data['route_parameters'] ?? []);
    }

    public function markAllRead(): RedirectResponse
    {
        Auth::guard('juri')->user()->unreadNotifications->markAsRead();

        return back()->with('status', __('juri.notifications.all_read'));
    }
}
