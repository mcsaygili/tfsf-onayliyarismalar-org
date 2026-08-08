<?php

namespace App\Contracts;

/**
 * SMS gönderim soyutlaması. Gerçek sağlayıcı (NetGSM vb.) ya da mock (log)
 * config'teki sürücüye göre bağlanır. Bkz. App\Providers\AppServiceProvider::register().
 */
interface SmsSender
{
    /**
     * Tek bir numaraya SMS gönderir.
     *
     * @param  string  $to  E.164 ya da yerel format telefon
     * @param  string  $message  Mesaj gövdesi
     * @return bool Gönderim kuyruğa/sağlayıcıya iletildiyse true
     */
    public function send(string $to, string $message): bool;
}
