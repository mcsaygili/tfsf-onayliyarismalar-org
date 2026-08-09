<?php

namespace App\Notifications\Temsilci;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends VerifyEmail
{
    protected function buildMailMessage($url): MailMessage
    {
        return (new MailMessage)
            ->subject(__('E-posta Adresinizi Doğrulayın'))
            ->line(__('Temsilci hesabınızı etkinleştirmek için lütfen aşağıdaki bağlantıya tıklayarak e-posta adresinizi doğrulayın.'))
            ->action(__('E-postayı Doğrula'), $url)
            ->line(__('Bu kaydı siz oluşturmadıysanız, herhangi bir işlem yapmanıza gerek yoktur.'));
    }

    /**
     * bkz. App\Notifications\Institution\VerifyEmailNotification —
     * Laravel'in temel VerifyEmail::verificationUrl() metodu route adını
     * `verification.verify` olarak sabit kodluyor, guard'dan bağımsız.
     * `temsilci.verification.verify` route'unu açıkça kullanmak, üretilen
     * URL'nin doğru subdomain'i (temsilci.*) içermesini garanti eder.
     */
    protected function verificationUrl($notifiable): string
    {
        return URL::temporarySignedRoute(
            'temsilci.verification.verify',
            Carbon::now()->addMinutes(config('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }
}
