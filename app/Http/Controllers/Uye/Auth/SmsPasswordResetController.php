<?php

namespace App\Http\Controllers\Uye\Auth;

use App\Contracts\SmsSender;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AccountSecurityContext;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
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
            'phone_number' => ['required', 'string', 'max:32'],
        ]);

        $this->ensureIsNotRateLimited($request);
        RateLimiter::hit($this->throttleKey($request), 600);

        $phoneNumber = $request->string('phone_number')->value();
        $code = (string) random_int(100000, 999999);
        $codeHash = Hash::make($code);
        $recordId = DB::transaction(function () use ($phoneNumber, $codeHash) {
            // Lock in the same order as verification. A phone must identify one account.
            $users = User::where('phone_number', $phoneNumber)->orderBy('id')->lockForUpdate()->get();
            if ($users->count() !== 1) {
                return null;
            }

            DB::table('sms_password_reset_codes')->where('phone_number', $phoneNumber)->delete();

            return DB::table('sms_password_reset_codes')->insertGetId([
                'user_id' => $users->first()->id,
                'security_context' => app(AccountSecurityContext::class)->fingerprint($users->first()),
                'phone_number' => $phoneNumber,
                'code_hash' => $codeHash,
                'expires_at' => now()->addMinutes(10),
                'created_at' => now(),
            ]);
        }, 3);

        if ($recordId !== null) {
            try {
                $sent = $sms->send($phoneNumber, __('TFSF Onaylı Yarışmalar şifre sıfırlama kodunuz: :code', ['code' => $code]));
            } catch (\Throwable $exception) {
                // Do not log the provider's response, phone number or plaintext code.
                Log::warning('SMS password reset delivery failed.', ['exception_type' => $exception::class]);
                $sent = false;
            }
            if (! $sent) {
                DB::table('sms_password_reset_codes')->where('id', $recordId)->delete();
            }
        }

        return redirect()
            ->route('password.sms.request')
            ->with('status', __('auth.sms_requested'))
            ->with('phone_number', $phoneNumber);
    }

    /**
     * @throws ValidationException
     */
    public function verifyAndReset(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'phone_number' => ['required', 'string', 'max:32'],
            'code' => ['required', 'string', 'regex:/^[0-9]{6}$/'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = DB::transaction(function () use ($validated) {
            $users = User::where('phone_number', $validated['phone_number'])->orderBy('id')->lockForUpdate()->get();
            if ($users->count() !== 1) {
                return null;
            }
            $user = $users->first();
            $record = DB::table('sms_password_reset_codes')
                ->where('phone_number', $validated['phone_number'])
                ->orderByDesc('id')->lockForUpdate()->first();

            if (! $record || $record->user_id !== $user->id
                || ! app(AccountSecurityContext::class)->matches($user, $record->security_context) || $record->expires_at <= now()->toDateTimeString()
                || $record->failed_attempts >= 5) {
                return null;
            }

            if (! Hash::check($validated['code'], $record->code_hash)) {
                DB::table('sms_password_reset_codes')->where('id', $record->id)->increment('failed_attempts');

                // Returning commits the failed attempt; throwing here would roll it back.
                return null;
            }

            $user->forceFill([
                'password' => Hash::make($validated['password']),
                'remember_token' => Str::random(60),
            ])->save();
            DB::table('sms_password_reset_codes')->where('phone_number', $validated['phone_number'])->delete();

            return $user;
        }, 3);

        if (! $user) {
            throw ValidationException::withMessages([
                'code' => __('auth.sms_invalid'),
            ]);
        }

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
        return 'uye-sms-reset|'.hash('sha256', $request->string('phone_number')->value());
    }
}
