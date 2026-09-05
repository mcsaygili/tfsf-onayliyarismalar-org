<?php

use App\Models\EysUser;
use App\Models\InstitutionStaff;
use App\Models\Juri;
use App\Models\Temsilci;
use App\Models\User;
use App\Services\PanelSession;
use App\Services\RegistrationDocumentScanService;
use App\Support\Documents\PdfDocumentScanner;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\Support\PassingDocumentScanner;

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
$input = json_decode(base64_decode($argv[1]), true, flags: JSON_THROW_ON_ERROR);
if (isset($input['storage_root'])) {
    $root = $input['storage_root'];
    if (! preg_match('#^'.preg_quote(sys_get_temp_dir(), '#').'/tfsf-entry-[a-f0-9-]+$#', $root)) {
        throw new RuntimeException('An isolated entry test storage root is required.');
    }
    config(['filesystems.disks.local.root' => $root.'/local', 'filesystems.disks.public.root' => $root.'/public']);
    Storage::forgetDisk(['local', 'public']);
}
if (isset($input['guard'])) {
    $models = ['web' => User::class, 'institution' => InstitutionStaff::class,
        'temsilci' => Temsilci::class, 'juri' => Juri::class, 'eys' => EysUser::class];
    Auth::guard($input['guard'])->setUser($models[$input['guard']]::findOrFail($input['user_id']));
    app('session.store')->start();
    app(PanelSession::class)->stamp(app('session.store'), Auth::guard($input['guard'])->user(), $input['guard']);
}
$events = 0;
Event::listen([Verified::class, PasswordReset::class], function () use (&$events) {
    $events++;
});
fwrite(STDOUT, 'connection:'.DB::selectOne('SELECT CONNECTION_ID() AS id')->id."\nready\n");
$deadline = microtime(true) + 15;
while (! is_file($argv[2])) {
    clearstatcache(true, $argv[2]);
    if (microtime(true) > $deadline) {
        exit(2);
    }
    usleep(10000);
}
if (isset($input['scan_document_id'])) {
    app()->instance(PdfDocumentScanner::class, new class extends PassingDocumentScanner
    {
        public function scan(string $path): array
        {
            usleep(250000);

            return parent::scan($path);
        }
    });
    $result = app(RegistrationDocumentScanService::class)->scan($input['scan_document_id']);
    echo 'result:'.json_encode(['status' => 200, 'scan_result' => $result])."\n";
    exit(0);
}
$request = Request::create($input['url'], $input['method'], $input['payload'] ?? []);
$response = $kernel->handle($request);
$result = ['status' => $response->getStatusCode(),
    'errors' => $request->hasSession() && $request->session()->has('errors'), 'events' => $events];
if ($input['capture_json'] ?? false) {
    $result['json'] = json_decode($response->getContent(), true);
}
fwrite(STDOUT, 'result:'.json_encode($result)."\n");
$kernel->terminate($request, $response);
