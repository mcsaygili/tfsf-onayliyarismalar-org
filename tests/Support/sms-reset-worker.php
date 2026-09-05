<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->instance('request', Request::create('http://localhost'));
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

if (! $app->environment('testing') || DB::connection()->getDatabaseName() !== 'tfsf_testing'
    || DB::connection()->getDriverName() !== 'mysql') {
    fwrite(STDERR, "This worker requires the isolated tfsf_testing MySQL database.\n");
    exit(1);
}

fwrite(STDOUT, "ready\n");
$deadline = microtime(true) + 15;
while (! is_file($argv[2])) {
    clearstatcache(true, $argv[2]);
    if (microtime(true) > $deadline) {
        exit(2);
    }
    usleep(10000);
}
$payload = json_decode(base64_decode($argv[1]), true, flags: JSON_THROW_ON_ERROR);
$request = Request::create(route('password.sms.verify'), 'POST', $payload, [], [], ['HTTP_ACCEPT' => 'application/json']);
$response = $kernel->handle($request);
fwrite(STDOUT, 'status:'.$response->getStatusCode()."\n");
$kernel->terminate($request, $response);
