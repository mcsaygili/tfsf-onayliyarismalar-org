<?php

namespace App\Notifications\Eys;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $url = route('eys.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject(__('Şifre Sıfırlama Bildirimi'))
            ->line(__('Bu e-postayı, EYS hesabınız için bir şifre sıfırlama isteği aldığımızdan dolayı alıyorsunuz.'))
            ->action(__('Şifreyi Sıfırla'), $url)
            ->line(__('Bu bağlantı :count dakika içinde geçerliliğini yitirecektir.', ['count' => config('auth.passwords.eys.expire')]))
            ->line(__('Eğer şifre sıfırlama talebinde bulunmadıysanız, herhangi bir işlem yapmanıza gerek yoktur.'));
    }
}
