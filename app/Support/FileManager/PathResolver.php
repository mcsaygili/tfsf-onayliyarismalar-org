<?php

namespace App\Support\FileManager;

use Illuminate\Support\Str;
use RuntimeException;

/**
 * FileManager yol/güvenlik çözümleyici.
 * Context (kapsam) kökü ile birleştirir, traversal/null-byte engeller, adları temizler.
 * Tüm döndürülen yollar 'public' diskine görelidir.
 */
class PathResolver
{
    /** Geçerli context anahtarı (yoksa varsayılan). */
    public function context(?string $context): string
    {
        $contexts = config('filemanager.contexts', []);

        return isset($contexts[$context]) ? $context : config('filemanager.default_context', 'common');
    }

    /** Context'in kök alt-klasörü (disk-göreli). 'all' → '' (tüm disk). */
    public function base(string $context): string
    {
        return trim((string) (config("filemanager.contexts.{$context}.root") ?? ''), '/');
    }

    /**
     * Context-göreli kullanıcı yolunu güvenli disk-göreli tam yola çevirir.
     * $rel her zaman context köküne GÖRELİDİR ('' = kök). Traversal reddedilir.
     */
    public function resolve(string $context, ?string $rel): string
    {
        $rel = str_replace('\\', '/', (string) $rel);

        if (str_contains($rel, '..') || str_contains($rel, "\0")) {
            throw new RuntimeException('Geçersiz yol.');
        }

        $rel = trim($rel, '/');
        $base = $this->base($context);

        $full = $base === '' ? $rel : ($rel === '' ? $base : $base.'/'.$rel);

        return trim($full, '/');
    }

    /** Verilen tam yolun context kökünün kendisi olup olmadığı (silinemez/yeniden adlandırılamaz). */
    public function isContextRoot(string $context, string $fullPath): bool
    {
        return trim($fullPath, '/') === $this->base($context);
    }

    /** Dosya/klasör adını güvenli hale getirir (uzantı korunur). */
    public function safeName(string $name): string
    {
        $ext = '';
        if (str_contains($name, '.')) {
            $ext = Str::lower(Str::afterLast($name, '.'));
            $name = Str::beforeLast($name, '.');
        }

        $slug = Str::slug($name) ?: 'dosya';

        return $ext !== '' ? "{$slug}.{$ext}" : $slug;
    }

    /** Bir uzantının yüklenmesine izin var mı? */
    public function extensionAllowed(string $ext): bool
    {
        $ext = Str::lower($ext);

        return ! in_array($ext, config('filemanager.blocked_extensions', []), true)
            && in_array($ext, config('filemanager.allowed_extensions', []), true);
    }

    public function isImage(string $ext): bool
    {
        return in_array(Str::lower($ext), config('filemanager.image_extensions', []), true);
    }
}
