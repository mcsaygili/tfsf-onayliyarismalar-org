<?php

namespace App\Notifications\Juri;

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
            ->line(__('Jüri hesabınızı etkinleştirmek için lütfen aşağıdaki bağlantıya tıklayarak e-posta adresinizi doğrulayın.'))
            ->action(__('E-postayı Doğrula'), $url)
            ->line(__('Bu kaydı siz oluşturmadıysanız, herhangi bir işlem yapmanıza gerek yoktur.'));
    }

    /**
     * bkz. App\Notifications\Institution\VerifyEmailNotification — guard'a
     * özgü route'u açıkça kullanarak doğrulama linkinin doğru subdomain'e
     * (juri.*) gitmesini garanti eder.
     */
    protected function verificationUrl($notifiable): string
    {
        return URL::temporarySignedRoute(
            'juri.verification.verify',
            Carbon::now()->addMinutes(config('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }
}
