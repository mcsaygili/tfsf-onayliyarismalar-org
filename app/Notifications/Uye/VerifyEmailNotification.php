<?php

namespace App\Notifications\Uye;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailNotification extends VerifyEmail
{
    protected function buildMailMessage($url): MailMessage
    {
        return (new MailMessage)
            ->subject(__('E-posta Adresinizi Doğrulayın'))
            ->line(__('Lütfen aşağıdaki bağlantıya tıklayarak e-posta adresinizi doğrulayın.'))
            ->action(__('E-postayı Doğrula'), $url)
            ->line(__('Bu hesabı siz oluşturmadıysanız, herhangi bir işlem yapmanıza gerek yoktur.'));
    }
}
