<?php

namespace App\Http\Controllers\Uye;

use App\Http\Controllers\Controller;
use App\Http\Requests\Uye\PhotoUpdateRequest;
use App\Http\Requests\Uye\PhotoUploadRequest;
use App\Models\Photo;
use App\Models\PhotoCategory;
use App\Models\PortfolioSetting;
use App\Support\Photo\ExifReader;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Intervention\Image\ImageManager;

/**
 * Üye (fotoğrafçı) kişisel fotoğraf portfolyosu — en fazla
 * PortfolioSetting::current()->max_photos_per_user fotoğraf (EYS "Sistem
 * Ayarları > Portfolyo Ayarları" üzerinden dinamik yönetilir). ProfileController'daki
 * gibi her işlem sadece $request->user() üzerinden yürür; authorizeOwner()
 * sahiplik kontrolü StaffController::authorizeSameInstitution() deseninin kopyası.
 */
class PortfolioController extends Controller
{
    public function index(Request $request): View
    {
        $photos = $request->user()->photos()
            ->with(['category.translations', 'equipment.equipmentModel.brand', 'equipment.equipmentModel.type.translations'])
            ->when($request->filled('category_id'), fn ($q) => $q->where('photo_category_id', $request->input('category_id')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->string('q');
                $q->where(fn ($sub) => $sub->where('title', 'like', "%{$term}%")
                    ->orWhere('tags_text', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%"));
            })
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('taken_at', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('taken_at', '<=', $request->input('date_to')))
            ->orderByDesc('taken_at')
            ->orderByDesc('created_at')
            ->get();

        return view('uye.portfolio.index', [
            'photos' => $photos,
            'photoCategories' => PhotoCategory::active()->ordered()->with('translations')->get(),
            'canUploadMore' => $request->user()->canUploadMorePhotos(),
            'maxPhotos' => PortfolioSetting::current()->max_photos_per_user,
            'userEquipment' => $request->user()->equipment()->with('equipmentModel.brand', 'equipmentModel.type.translations')->get(),
            'filter' => [
                'category_id' => $request->input('category_id', ''),
                'q' => $request->input('q', ''),
                'date_from' => $request->input('date_from', ''),
                'date_to' => $request->input('date_to', ''),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        return view('uye.portfolio.create', [
            'photoCategories' => PhotoCategory::active()->ordered()->with('translations')->get(),
            'userEquipment' => $request->user()->equipment()->with('equipmentModel.brand', 'equipmentModel.type.translations')->get(),
        ]);
    }

    public function store(PhotoUploadRequest $request, ExifReader $exifReader): RedirectResponse
    {
        $file = $request->file('photo');
        $uuid = (string) Str::uuid();
        $ext = Str::lower($file->getClientOriginalExtension());
        $dir = "portfolio/{$request->user()->id}";
        $diskPath = "{$dir}/{$uuid}.{$ext}";
        $disk = Storage::disk('public');

        $file->storeAs($dir, "{$uuid}.{$ext}", 'public');

        $exif = $exifReader->read($disk->path($diskPath));

        $thumbPath = "{$dir}/{$uuid}_thumb.{$ext}";

        try {
            $img = ImageManager::imagick()->read($disk->path($diskPath));
            $img->scaleDown(width: 640);
            $disk->put($thumbPath, (string) $img->encodeByExtension($ext, quality: 80));
        } catch (\Throwable) {
            $thumbPath = null;
        }

        $tags = $this->parseTags($request->input('tags'));

        $photo = $request->user()->photos()->create(array_merge($exif, [
            'photo_category_id' => $request->input('photo_category_id'),
            'title' => $request->input('title'),
            'location' => $request->input('location'),
            'taken_at' => $request->input('taken_at'),
            'tags' => $tags,
            'tags_text' => implode(' ', $tags),
            'description' => $request->input('description'),
            'disk_path' => $diskPath,
            'thumb_path' => $thumbPath,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size_bytes' => $file->getSize(),
        ]));

        $photo->equipment()->sync($request->input('equipment', []));

        return redirect()->route('portfolio.index')->with('status', __('uye.portfolio.uploaded'));
    }

    public function edit(Request $request, Photo $photo): View
    {
        $this->authorizeOwner($photo, $request);

        $photo->load(['category.translations', 'equipment.equipmentModel.brand', 'equipment.equipmentModel.type.translations']);

        return view('uye.portfolio.edit', [
            'photo' => $photo,
            'photoCategories' => PhotoCategory::active()->ordered()->with('translations')->get(),
            'userEquipment' => $request->user()->equipment()->with('equipmentModel.brand', 'equipmentModel.type.translations')->get(),
        ]);
    }

    public function update(PhotoUpdateRequest $request, Photo $photo): RedirectResponse
    {
        $this->authorizeOwner($photo, $request);

        $tags = $this->parseTags($request->input('tags'));

        $photo->update([
            'photo_category_id' => $request->input('photo_category_id'),
            'title' => $request->input('title'),
            'location' => $request->input('location'),
            'taken_at' => $request->input('taken_at'),
            'tags' => $tags,
            'tags_text' => implode(' ', $tags),
            'description' => $request->input('description'),
        ]);

        $photo->equipment()->sync($request->input('equipment', []));

        return redirect()->route('portfolio.index')->with('status', __('uye.portfolio.updated'));
    }

    public function destroy(Request $request, Photo $photo): RedirectResponse
    {
        $this->authorizeOwner($photo, $request);

        Storage::disk('public')->delete(array_filter([$photo->disk_path, $photo->thumb_path]));
        $photo->delete();

        return redirect()->route('portfolio.index')->with('status', __('uye.portfolio.deleted'));
    }

    /** @return array<int, string> */
    private function parseTags(?string $raw): array
    {
        return collect(explode(',', (string) $raw))
            ->map(fn ($tag) => trim($tag))
            ->filter()
            ->values()
            ->all();
    }

    private function authorizeOwner(Photo $photo, Request $request): void
    {
        if ($photo->user_id !== $request->user()->id) {
            throw new AuthorizationException;
        }
    }
}
