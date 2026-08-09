<?php

use App\Http\Controllers\ResendEventWebhookController;
use Illuminate\Support\Facades\Route;

/**
 * Webhook uç noktaları — bilinçli olarak Route::domain() SARMASIZ. Diğer
 * tüm route dosyalarının aksine (bkz. web.php'deki açıklama), webhook
 * sağlayıcıları (Resend) belirli bir modül subdomain'ine değil, uygulamanın
 * temel URL'ine istek atar; bu yüzden burada domain scoping YOK.
 */
Route::post('webhooks/resend', ResendEventWebhookController::class)->name('webhooks.resend');
