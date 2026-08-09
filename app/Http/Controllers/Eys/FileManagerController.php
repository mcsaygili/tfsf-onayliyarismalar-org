<?php

namespace App\Http\Controllers\Eys;

use App\Http\Controllers\Controller;
use App\Support\FileManager\ContextAccess;
use App\Support\FileManager\PathResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Intervention\Image\ImageManager;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * EYS — FileManager (dosya-sistemi tabanlı medya yöneticisi). Tam sayfa
 * için JSON API. public diski üzerinde çalışır; context-bazlı kök
 * kısıtlaması (PathResolver) ile izole.
 */
class FileManagerController extends Controller
{
    public function __construct(
        private readonly PathResolver $resolver,
        private readonly ContextAccess $access,
    ) {}

    private function disk()
    {
        return Storage::disk(config('filemanager.disk', 'public'));
    }

    /** Context'i normalize eder ve erişimi doğrular (yetkisizse 403). */
    private function context(Request $request): string
    {
        $context = $this->resolver->context($request->input('context') ?? $request->query('context'));
        abort_unless($this->access->canAccess($request->user('eys'), $context), 403);

        return $context;
    }

    /** Yolu güvenle çözer; geçersizse 422 (500 yerine). */
    private function resolved(string $context, ?string $rel): string
    {
        try {
            return $this->resolver->resolve($context, $rel);
        } catch (RuntimeException $e) {
            abort(422, $e->getMessage());
        }
    }

    /** Standalone yönetim sayfası. */
    public function index(Request $request): View
    {
        $contexts = $this->access->allowedContexts($request->user('eys'));
        abort_if($contexts === [], 403);

        return view('eys.filemanager.index', [
            'contexts' => collect($contexts),
        ]);
    }

    /** Klasör içeriği (JSON) — thumbnail, sayfalama, özyinelemeli arama. */
    public function browse(Request $request): JsonResponse
    {
        $context = $this->context($request);
        $rel = (string) $request->query('path', '');
        $full = $this->resolved($context, $rel);
        $base = $this->resolver->base($context);
        $disk = $this->disk();
        $filetype = $request->query('filetype');
        $q = mb_strtolower(trim((string) $request->query('q', '')));
        $recursive = $request->boolean('recursive') && $q !== '';

        $folders = $recursive ? collect() : collect($disk->directories($full))
            ->reject(fn ($d) => str_starts_with(basename($d), '.'))
            ->map(fn ($d) => ['name' => basename($d), 'path' => $this->childRel($rel, basename($d))])
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values();

        $raw = $recursive ? $disk->allFiles($base) : $disk->files($full);

        $files = collect($raw)
            ->reject(fn ($f) => str_starts_with(basename($f), '.') || str_contains($f, '/.thumbs/') || str_starts_with($f, '.thumbs/'))
            ->map(function ($f) use ($disk, $context, $base, $rel, $recursive) {
                $name = basename($f);
                $ext = Str::lower(Str::afterLast($name, '.'));
                $path = $recursive ? $this->stripBase($base, $f) : $this->childRel($rel, $name);
                $isImage = $this->resolver->isImage($ext);

                return [
                    'name' => $name,
                    'ext' => $ext,
                    'url' => $disk->url($f),
                    'thumb' => $isImage && $ext !== 'svg'
                        ? route('eys.filemanager.thumb', ['context' => $context, 'path' => $path])
                        : $disk->url($f),
                    'path' => $path,
                    'size' => $disk->size($f),
                    'modified' => $disk->lastModified($f),
                    'isImage' => $isImage,
                ];
            })
            ->when($filetype === 'image', fn ($c) => $c->filter(fn ($x) => $x['isImage']))
            ->when($q !== '', fn ($c) => $c->filter(fn ($x) => str_contains(mb_strtolower($x['name']), $q)))
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values();

        $perPage = (int) config('filemanager.per_page', 60);
        $page = max(1, (int) $request->query('page', 1));
        $total = $files->count();
        $files = $files->forPage($page, $perPage)->values();

        return response()->json([
            'context' => $context,
            'path' => trim($rel, '/'),
            'recursive' => $recursive,
            'folders' => $folders,
            'files' => $files,
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
        ]);
    }

    /** On-demand thumbnail (cache: .thumbs/{w}/...). Görsel değilse orijinali servis eder. */
    public function thumb(Request $request)
    {
        $context = $this->context($request);
        $full = $this->resolved($context, (string) $request->query('path', ''));
        $disk = $this->disk();
        abort_unless($disk->fileExists($full), 404);

        $ext = Str::lower(Str::afterLast($full, '.'));
        if (! $this->resolver->isImage($ext) || $ext === 'svg') {
            return $disk->response($full);
        }

        $w = (int) ($request->query('w') ?: config('filemanager.thumb_width', 240));
        $cache = '.thumbs/'.$w.'/'.$full;

        if (! $disk->fileExists($cache)) {
            try {
                $img = ImageManager::imagick()->read($disk->path($full));
                $img->scaleDown(width: $w);
                $disk->put($cache, (string) $img->encodeByExtension($ext, quality: 80));
            } catch (Throwable) {
                return $disk->response($full);
            }
        }

        return $disk->response($cache);
    }

    public function upload(Request $request): JsonResponse
    {
        $context = $this->context($request);
        $full = $this->resolved($context, (string) $request->input('path', ''));
        $disk = $this->disk();
        $maxKb = (int) config('filemanager.max_upload_mb', 25) * 1024;
        $filetype = $request->input('filetype');

        $request->validate([
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['file', "max:{$maxKb}"],
        ]);

        $saved = 0;
        $errors = [];

        foreach ($request->file('files') as $file) {
            $ext = Str::lower($file->getClientOriginalExtension());

            if (! $this->resolver->extensionAllowed($ext) || ! $this->mimeSafe($file, $ext)) {
                $errors[] = $file->getClientOriginalName().' — '.__('eys.file_manager.ext_not_allowed');

                continue;
            }

            if ($filetype === 'image' && ! $this->resolver->isImage($ext)) {
                $errors[] = $file->getClientOriginalName().' — '.__('eys.file_manager.image_only');

                continue;
            }

            $name = $this->uniqueName($full, $this->resolver->safeName($file->getClientOriginalName()));
            $target = trim($full.'/'.$name, '/');

            if ($ext === 'svg') {
                $disk->put($target, $this->sanitizeSvg((string) file_get_contents($file->getRealPath())));
            } else {
                $file->storeAs($full, $name, config('filemanager.disk', 'public'));
            }
            $saved++;
        }

        return response()->json([
            'status' => $saved > 0 ? 'success' : 'error',
            'saved' => $saved,
            'errors' => $errors,
        ]);
    }

    public function createFolder(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:128']]);

        $context = $this->context($request);
        $full = $this->resolved($context, (string) $request->input('path', ''));
        $folder = Str::slug($data['name']) ?: 'klasor';
        $target = trim($full.'/'.$folder, '/');

        if ($this->disk()->exists($target)) {
            return response()->json(['status' => 'error', 'message' => __('eys.file_manager.folder_exists')], 422);
        }

        $this->disk()->makeDirectory($target);

        return response()->json(['status' => 'success']);
    }

    public function rename(Request $request): JsonResponse
    {
        $data = $request->validate([
            'path' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $context = $this->context($request);
        $full = $this->resolved($context, $data['path']);

        if ($this->resolver->isContextRoot($context, $full)) {
            return response()->json(['status' => 'error', 'message' => __('eys.file_manager.cannot_modify_root')], 422);
        }

        $parent = Str::contains($full, '/') ? Str::beforeLast($full, '/') : '';
        $newName = $this->resolver->safeName($data['name']);
        $target = trim(($parent === '' ? '' : $parent.'/').$newName, '/');

        if ($target !== $full && $this->disk()->exists($target)) {
            return response()->json(['status' => 'error', 'message' => __('eys.file_manager.name_exists')], 422);
        }

        $this->disk()->move($full, $target);

        return response()->json(['status' => 'success']);
    }

    public function delete(Request $request): JsonResponse
    {
        $data = $request->validate(['path' => ['required', 'string']]);

        $context = $this->context($request);
        $full = $this->resolved($context, $data['path']);
        $disk = $this->disk();

        if ($this->resolver->isContextRoot($context, $full) || $full === '') {
            return response()->json(['status' => 'error', 'message' => __('eys.file_manager.cannot_modify_root')], 422);
        }

        if ($disk->fileExists($full)) {
            $disk->delete($full);
        } else {
            $disk->deleteDirectory($full);
        }

        return response()->json(['status' => 'success']);
    }

    /** Toplu silme (dosya/klasör). */
    public function bulkDelete(Request $request): JsonResponse
    {
        $data = $request->validate(['paths' => ['required', 'array', 'min:1'], 'paths.*' => ['string']]);
        $context = $this->context($request);
        $disk = $this->disk();
        $deleted = 0;

        foreach ($data['paths'] as $p) {
            $full = $this->resolved($context, $p);
            if ($this->resolver->isContextRoot($context, $full) || $full === '') {
                continue;
            }
            if ($disk->fileExists($full)) {
                $disk->delete($full);
            } else {
                $disk->deleteDirectory($full);
            }
            $deleted++;
        }

        return response()->json(['status' => 'success', 'deleted' => $deleted]);
    }

    /** Dosya/klasörleri context içinde başka klasöre taşı. */
    public function move(Request $request): JsonResponse
    {
        $data = $request->validate([
            'paths' => ['required', 'array', 'min:1'],
            'paths.*' => ['string'],
            'target' => ['nullable', 'string'],
        ]);

        $context = $this->context($request);
        $targetDir = $this->resolved($context, (string) ($data['target'] ?? ''));
        $disk = $this->disk();
        $moved = 0;

        foreach ($data['paths'] as $p) {
            $full = $this->resolved($context, $p);
            if ($this->resolver->isContextRoot($context, $full) || $full === '') {
                continue;
            }
            if ($targetDir === $full || str_starts_with($targetDir.'/', $full.'/')) {
                continue;
            }

            $name = basename($full);
            $dest = trim($targetDir.'/'.$name, '/');
            if ($dest === $full) {
                continue;
            }
            if ($disk->fileExists($dest) || $disk->directoryExists($dest)) {
                $name = $this->uniqueName($targetDir, $this->resolver->safeName($name));
                $dest = trim($targetDir.'/'.$name, '/');
            }

            $disk->move($full, $dest);
            $moved++;
        }

        return response()->json(['status' => 'success', 'moved' => $moved]);
    }

    /** Dosyayı indirir (inline çalıştırma yok). */
    public function download(Request $request): StreamedResponse
    {
        $context = $this->context($request);
        $full = $this->resolved($context, (string) $request->query('path', ''));

        abort_unless($this->disk()->fileExists($full), 404);

        return $this->disk()->download($full, basename($full));
    }

    /** Gerçek MIME türü güvenli mi? (uzantı kandırmacasına karşı) */
    private function mimeSafe($file, string $ext): bool
    {
        $mime = strtolower((string) ($file->getMimeType() ?: ''));

        foreach (['php', 'html', 'javascript', 'x-sh', 'x-perl', 'x-python', 'executable', 'x-msdownload'] as $bad) {
            if (str_contains($mime, $bad)) {
                return false;
            }
        }

        if ($this->resolver->isImage($ext) && $ext !== 'svg' && ! str_starts_with($mime, 'image/')) {
            return false;
        }

        return true;
    }

    /** SVG içeriğinden script/olay-işleyici/javascript: temizler. */
    private function sanitizeSvg(string $svg): string
    {
        $svg = preg_replace('#<script[^>]*>.*?</script>#is', '', $svg) ?? $svg;
        $svg = preg_replace('#\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $svg) ?? $svg;
        $svg = preg_replace('#(href|xlink:href)\s*=\s*("|\')\s*javascript:[^"\']*\2#i', '', $svg) ?? $svg;

        return $svg;
    }

    /** Aynı klasörde çakışan adı -1, -2 ... ile benzersizleştirir. */
    private function uniqueName(string $dir, string $name): string
    {
        $disk = $this->disk();
        $base = Str::contains($name, '.') ? Str::beforeLast($name, '.') : $name;
        $ext = Str::contains($name, '.') ? '.'.Str::afterLast($name, '.') : '';

        $candidate = $name;
        $i = 1;
        while ($disk->fileExists(trim($dir.'/'.$candidate, '/'))) {
            $candidate = $base.'-'.$i++.$ext;
        }

        return $candidate;
    }

    private function childRel(string $rel, string $name): string
    {
        $rel = trim($rel, '/');

        return $rel === '' ? $name : $rel.'/'.$name;
    }

    /** Disk-göreli tam yoldan context kökünü çıkarıp context-göreli yol döndürür. */
    private function stripBase(string $base, string $full): string
    {
        $base = trim($base, '/');
        if ($base === '') {
            return trim($full, '/');
        }

        return ltrim(Str::after($full, $base), '/');
    }
}
