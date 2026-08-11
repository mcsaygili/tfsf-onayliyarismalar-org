<?php

namespace App\Http\Controllers\Eys;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceMode;
use App\Models\PortfolioSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SystemSettingsController extends Controller
{
    public function portfolio(): View
    {
        return view('eys.system-settings.portfolio', [
            'settings' => PortfolioSetting::current(),
        ]);
    }

    public function updatePortfolio(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'max_photos_per_user' => ['required', 'integer', 'min:1', 'max:1000'],
        ]);

        $settings = PortfolioSetting::current();
        $settings->update([
            'max_photos_per_user' => $data['max_photos_per_user'],
            'updated_by' => Auth::guard('eys')->id(),
        ]);

        return redirect()->route('eys.system-settings.portfolio')->with('status', __('eys.system_settings.portfolio_updated'));
    }

    public function maintenance(): View
    {
        $settings = MaintenanceMode::query()->get()->keyBy('module');

        return view('eys.system-settings.maintenance', [
            'modules' => MaintenanceMode::MODULES,
            'settings' => $settings,
        ]);
    }

    public function updateMaintenance(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'modules' => ['required', 'array'],
            'modules.*.enabled' => ['required', 'in:0,1'],
            'modules.*.message' => ['nullable', 'string', 'max:2000'],
        ]);

        foreach (MaintenanceMode::MODULES as $module) {
            if (! isset($data['modules'][$module])) {
                continue;
            }

            MaintenanceMode::query()->updateOrCreate(
                ['module' => $module],
                [
                    'enabled' => (bool) $data['modules'][$module]['enabled'],
                    'message' => $data['modules'][$module]['message'] ?: null,
                    'updated_by' => Auth::guard('eys')->id(),
                ]
            );
        }

        return redirect()->route('eys.system-settings.maintenance')->with('status', __('eys.system_settings.maintenance_updated'));
    }
}
