<?php

namespace App\Notifications\Temsilci;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $url = route('temsilci.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject(__('Şifre Sıfırlama Bildirimi'))
            ->line(__('Bu e-postayı, temsilci hesabınız için bir şifre sıfırlama isteği aldığımızdan dolayı alıyorsunuz.'))
            ->action(__('Şifreyi Sıfırla'), $url)
            ->line(__('Bu bağlantı :count dakika içinde geçerliliğini yitirecektir.', ['count' => config('auth.passwords.temsilci.expire')]))
            ->line(__('Eğer şifre sıfırlama talebinde bulunmadıysanız, herhangi bir işlem yapmanıza gerek yoktur.'));
    }
}
