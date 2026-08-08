<?php

namespace App\Notifications\Uye;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * `route()` guard-özel isimlere göre üretildiğinde, hedef route
 * `Route::domain(config('domains.uye'))` altında tanımlı olduğu için
 * doğru subdomain'i içeren mutlak URL otomatik üretilir — ayrıca bir
 * "absolute URL builder" yardımcı sınıfına gerek yok.
 */
class ResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject(__('Şifre Sıfırlama Bildirimi'))
            ->line(__('Bu e-postayı, hesabınız için bir şifre sıfırlama isteği aldığımızdan dolayı alıyorsunuz.'))
            ->action(__('Şifreyi Sıfırla'), $url)
            ->line(__('Bu bağlantı :count dakika içinde geçerliliğini yitirecektir.', ['count' => config('auth.passwords.users.expire')]))
            ->line(__('Eğer şifre sıfırlama talebinde bulunmadıysanız, herhangi bir işlem yapmanıza gerek yoktur.'));
    }
}
