<?php

namespace App\Notifications\Institution;

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
            ->line(__('Kurum hesabınızı etkinleştirmek için lütfen aşağıdaki bağlantıya tıklayarak e-posta adresinizi doğrulayın.'))
            ->action(__('E-postayı Doğrula'), $url)
            ->line(__('Bu kaydı siz oluşturmadıysanız, herhangi bir işlem yapmanıza gerek yoktur.'));
    }

    /**
     * Laravel'in temel VerifyEmail::verificationUrl() metodu route adını
     * `verification.verify` olarak SABİT kodluyor — bu bizim Uye modülümüzün
     * (önek'siz) route ismiyle çakıştığı için Institution kayıtlarında
     * doğrulama linki yanlışlıkla kök domain'e (Uye) gidiyordu. Burada
     * `institution.verification.verify` route'unu açıkça kullanıyoruz —
     * bu route zaten `Route::domain(config('domains.institution'))` içinde
     * tanımlı olduğundan, üretilen mutlak URL otomatik olarak doğru
     * subdomain'i (kurum.*) içerir.
     */
    protected function verificationUrl($notifiable): string
    {
        return URL::temporarySignedRoute(
            'institution.verification.verify',
            Carbon::now()->addMinutes(config('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }
}
