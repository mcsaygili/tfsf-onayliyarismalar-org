<?php

namespace App\Http\Controllers\Uye\Auth;

use App\Contracts\SmsSender;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Üye'nin SMS ile şifre sıfırlama akışı — eski sistemdeki NetGSM tabanlı
 * SMS sıfırlamanın modern karşılığı (bkz. docs/MEVCUT-YAPI-ANALIZ.md §8).
 * `sms_password_reset_codes` tablosu Laravel'in password_reset_tokens'ının
 * telefon-anahtarlı küçük karşılığıdır: kod her zaman hash'lenerek saklanır,
 * 10 dakika geçerlidir.
 */
class SmsPasswordResetController extends Controller
{
    public function create(): View
    {
        return view('uye.auth.forgot-password-sms');
    }

    /**
     * @throws ValidationException
     */
    public function sendCode(Request $request, SmsSender $sms): RedirectResponse
    {
        $request->validate([
            'phone_number' => ['required', 'string'],
        ]);

        $this->ensureIsNotRateLimited($request);

        $phoneNumber = $request->string('phone_number')->value();
        $exists = User::where('phone_number', $phoneNumber)->exists();

        // Numara kayıtlı değilse bile aynı yanıtı döndürüyoruz — telefon
        // numarası enumeration'ını önlemek için (bkz. e-posta broker'ının
        // RESET_LINK_SENT davranışıyla tutarlı yaklaşım).
        if ($exists) {
            $code = (string) random_int(100000, 999999);

            DB::table('sms_password_reset_codes')->insert([
                'phone_number' => $phoneNumber,
                'code_hash' => Hash::make($code),
                'expires_at' => now()->addMinutes(10),
                'created_at' => now(),
            ]);

            $sms->send($phoneNumber, __('TFSF Onaylı Yarışmalar şifre sıfırlama kodunuz: :code', ['code' => $code]));
        }

        RateLimiter::hit($this->throttleKey($request));

        return redirect()
            ->route('password.sms.request')
            ->with('status', __('Numaranız sistemde kayıtlıysa, doğrulama kodu gönderildi.'))
            ->with('phone_number', $phoneNumber);
    }

    /**
     * @throws ValidationException
     */
    public function verifyAndReset(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'phone_number' => ['required', 'string'],
            'code' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $record = DB::table('sms_password_reset_codes')
            ->where('phone_number', $validated['phone_number'])
            ->where('expires_at', '>=', now())
            ->orderByDesc('id')
            ->first();

        if (! $record || ! Hash::check($validated['code'], $record->code_hash)) {
            throw ValidationException::withMessages([
                'code' => __('Kod geçersiz veya süresi dolmuş.'),
            ]);
        }

        $user = User::where('phone_number', $validated['phone_number'])->firstOrFail();

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'remember_token' => Str::random(60),
        ])->save();

        DB::table('sms_password_reset_codes')->where('phone_number', $validated['phone_number'])->delete();

        event(new PasswordReset($user));

        return redirect()->route('login')->with('status', __('Şifreniz güncellendi, giriş yapabilirsiniz.'));
    }

    /**
     * @throws ValidationException
     */
    private function ensureIsNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), 3)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'phone_number' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    private function throttleKey(Request $request): string
    {
        return 'uye-sms-reset|'.$request->string('phone_number').'|'.$request->ip();
    }
}
