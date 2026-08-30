<?php

// Her modül kendi subdomain'ine Route::domain(config('domains.x')) ile
// sarılı olarak tanımlanıyor (bkz. config/domains.php) — Uye dahil, un-scoped
// bir route diğer subdomain'lerin route'larını gölgeleme riski taşırdı.
require __DIR__.'/uye.php';
require __DIR__.'/institution.php';
require __DIR__.'/temsilci.php';
require __DIR__.'/juri.php';
require __DIR__.'/eys.php';
require __DIR__.'/result.php';
require __DIR__.'/webhooks.php';
