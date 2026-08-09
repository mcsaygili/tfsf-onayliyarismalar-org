<?php

namespace App\Http\Controllers\Eys;

use App\Http\Controllers\Controller;
use App\Models\MailEvent;
use App\Models\MailSendLog;
use App\Models\MailSetting;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

/**
 * EYS yönetici paneli — Mail İstemcisi (Resend). Tek sağlayıcılı, sade
 * mimari: gönderim ayarları + gönderim/olay günlükleri + test gönderimi.
 */
class MailClientController extends Controller
{
    public function dashboard(): View
    {
        return view('eys.mail-client.dashboard', [
            'settings' => MailSetting::current(),
            'totalSent' => MailSendLog::count(),
            'sentToday' => MailSendLog::whereDate('created_at', today())->count(),
            'totalEvents' => MailEvent::count(),
            'recentLogs' => MailSendLog::latest()->limit(5)->get(),
        ]);
    }

    public function settings(): View
    {
        return view('eys.mail-client.settings', [
            'settings' => MailSetting::current(),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'daily_quota' => ['nullable', 'integer', 'min:1'],
            'rate_per_second' => ['nullable', 'integer', 'min:1'],
            'enabled' => ['required', 'in:0,1'],
        ]);

        $settings = MailSetting::current();
        $settings->update([
            'daily_quota' => $data['daily_quota'] ?: null,
            'rate_per_second' => $data['rate_per_second'] ?: null,
            'enabled' => (bool) $data['enabled'],
            'updated_by' => Auth::guard('eys')->id(),
        ]);

        return redirect()->route('eys.mail-client.settings')->with('status', __('eys.mail_client.settings_updated'));
    }

    public function test(): View
    {
        return view('eys.mail-client.test');
    }

    public function sendTest(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'to' => ['required', 'email'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        Mail::raw($data['message'], function ($mail) use ($data) {
            $mail->to($data['to'])->subject($data['subject']);
        });

        return redirect()->route('eys.mail-client.test')->with('status', __('eys.mail_client.test_sent'));
    }

    public function logs(Request $request): View
    {
        $dateFrom = $this->parseDate($request->input('date_from'));
        $dateTo = $this->parseDate($request->input('date_to'));

        $logs = MailSendLog::query()
            ->when($request->filled('to'), fn ($q) => $q->where('to', 'like', '%'.$request->string('to').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('eys.mail-client.logs', [
            'logs' => $logs,
            'filter' => [
                'to' => $request->input('to', ''),
                'status' => $request->input('status', ''),
                'date_from' => $request->input('date_from', ''),
                'date_to' => $request->input('date_to', ''),
            ],
        ]);
    }

    public function activity(Request $request): View
    {
        $dateFrom = $this->parseDate($request->input('date_from'));
        $dateTo = $this->parseDate($request->input('date_to'));

        $events = MailEvent::query()
            ->with('mailSendLog')
            ->when($request->filled('event_type'), fn ($q) => $q->where('event_type', $request->input('event_type')))
            ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('eys.mail-client.activity', [
            'events' => $events,
            'eventTypes' => MailEvent::query()->distinct()->orderBy('event_type')->pluck('event_type'),
            'filter' => [
                'event_type' => $request->input('event_type', ''),
                'date_from' => $request->input('date_from', ''),
                'date_to' => $request->input('date_to', ''),
            ],
        ]);
    }

    /** URL'den gelen tarih değerini güvenle ayrıştırır — geçersiz girdi sessizce yok sayılır. */
    private function parseDate(?string $value): ?CarbonImmutable
    {
        if (blank($value)) {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('Y-m-d', $value)?->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }
}
