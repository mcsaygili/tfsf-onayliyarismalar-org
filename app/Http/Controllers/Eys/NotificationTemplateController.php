<?php

namespace App\Http\Controllers\Eys;

use App\Http\Controllers\Controller;
use App\Models\NotificationTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationTemplateController extends Controller
{
    public function index(): View
    {
        return view('eys.mail-client.templates.index', [
            'templates' => NotificationTemplate::query()->with('translations')->orderBy('name')->get(),
        ]);
    }

    public function edit(NotificationTemplate $notificationTemplate): View
    {
        $notificationTemplate->load('translations');

        return view('eys.mail-client.templates.edit', ['template' => $notificationTemplate]);
    }

    public function update(Request $request, NotificationTemplate $notificationTemplate): RedirectResponse
    {
        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
            'translations' => ['required', 'array'],
            'translations.tr.subject' => ['required', 'string', 'max:255'],
            'translations.tr.greeting' => ['nullable', 'string', 'max:255'],
            'translations.tr.body' => ['required', 'string', 'max:10000'],
            'translations.tr.action_label' => ['nullable', 'string', 'max:255'],
            'translations.en.subject' => ['required', 'string', 'max:255'],
            'translations.en.greeting' => ['nullable', 'string', 'max:255'],
            'translations.en.body' => ['required', 'string', 'max:10000'],
            'translations.en.action_label' => ['nullable', 'string', 'max:255'],
        ]);

        $notificationTemplate->update(['is_active' => $data['is_active']]);
        foreach (['tr', 'en'] as $locale) {
            $notificationTemplate->translations()->updateOrCreate(
                ['locale' => $locale],
                $data['translations'][$locale],
            );
        }

        return redirect()->route('eys.mail-client.templates.index')->with('status', __('eys.mail_client.template_updated'));
    }
}
