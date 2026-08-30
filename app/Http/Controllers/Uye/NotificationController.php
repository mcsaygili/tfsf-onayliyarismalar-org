<?php

namespace App\Http\Controllers\Uye;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        return view('uye.notifications.index', ['notifications' => $request->user()->notifications()->paginate(20)]);
    }

    public function show(Request $request, string $notification): RedirectResponse
    {
        $item = $request->user()->notifications()->findOrFail($notification);
        $item->markAsRead();

        return redirect()->route($item->data['route_name'] ?? 'dashboard', $item->data['route_parameters'] ?? []);
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('status', __('uye.notifications.all_read'));
    }
}
