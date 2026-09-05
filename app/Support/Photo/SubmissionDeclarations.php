<?php

namespace App\Support\Photo;

use App\Models\CompetitionCategory;
use App\Models\CompetitionSubmission;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SubmissionDeclarations
{
    public const CATEGORY_FLAGS = ['photo_story_required', 'category_story_required', 'photo_order_required'];

    public static function summary(CompetitionCategory $category, ?string $locale = null): string
    {
        $parts = [__('declarations.requirements_hint', [], $locale)];
        foreach (self::CATEGORY_FLAGS as $flag) {
            if ($category->{$flag}) {
                $parts[] = __('declarations.'.$flag, [], $locale);
            }
        }

        if ($category->photos_grouped) {
            $parts[] = __('series.requirement', [], $locale);
        }

        return implode(' · ', $parts);
    }

    public static function fromMetadata(array $metadata): array
    {
        $date = $metadata['taken_at'] ?? null;
        // Only the member's portfolio date, never exif_captured_at/DateTimeOriginal.
        $date = $date instanceof \DateTimeInterface ? $date->format('Y-m-d') : ($date ? substr((string) $date, 0, 10) : null);

        return ['title' => $metadata['title'] ?? null, 'location' => $metadata['location'] ?? null, 'taken_on' => $date, 'story' => null];
    }

    public static function rules(CompetitionCategory $category, bool $complete): array
    {
        $required = $complete ? 'required' : 'nullable';

        return [
            'category_story' => [$complete && $category->category_story_required ? 'required' : 'nullable', 'string', 'max:4000'],
            'photos' => ['required', 'array', 'min:1', 'max:20'],
            'photos.*' => ['required', 'array:id,title,location,taken_on,story,position'],
            'photos.*.id' => ['required', 'uuid', 'distinct'],
            'photos.*.title' => [$required, 'string', 'max:255'],
            'photos.*.location' => [$required, 'string', 'max:255'],
            'photos.*.taken_on' => [$required, 'date_format:Y-m-d'],
            'photos.*.story' => [$complete && $category->photo_story_required ? 'required' : 'nullable', 'string', 'max:4000'],
            'photos.*.position' => [$category->requiresPhotoOrder() ? 'required' : 'nullable', 'integer', 'min:1', 'max:20', 'distinct'],
        ];
    }

    public static function validate(CompetitionCategory $category, array $payload, bool $complete): array
    {
        // Service callers receive the same blank-string semantics as HTTP callers.
        if (is_array($payload['photos'] ?? null)) {
            foreach ($payload['photos'] as &$photo) {
                if (! is_array($photo)) {
                    continue;
                }
                foreach (['title', 'location', 'taken_on', 'story'] as $key) {
                    if (is_string($photo[$key] ?? null)) {
                        $trimmed = trim($photo[$key]);
                        $photo[$key] = $trimmed === '' ? null : $trimmed;
                    }
                }
            }
            unset($photo);
        }
        if (is_string($payload['category_story'] ?? null)) {
            $trimmed = trim($payload['category_story']);
            $payload['category_story'] = $trimmed === '' ? null : $trimmed;
        }
        $names = ['category_story' => __('declarations.category_story')];
        foreach (['title', 'location', 'taken_on', 'story', 'position'] as $key) {
            $names['photos.*.'.$key] = __('declarations.'.$key);
        }
        $validated = Validator::make($payload, self::rules($category, $complete), [], $names)->validate();
        if ($category->requiresPhotoOrder()) {
            $positions = array_column($validated['photos'], 'position');
            sort($positions, SORT_NUMERIC);
            if (array_map('intval', $positions) !== range(1, count($validated['photos']))) {
                throw ValidationException::withMessages(['photos' => __('declarations.order_incomplete')]);
            }
        }

        return $validated;
    }

    public static function assertComplete(CompetitionSubmission $submission): void
    {
        $photos = $submission->activePhotos;
        try {
            self::validate($submission->category, ['category_story' => $submission->category_story,
                'photos' => $photos->values()->map(fn ($photo, $index) => [
                    'id' => $photo->id, ...$photo->declarationData(), 'position' => $index + 1,
                ])->all()], true);
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages(['entry' => __('declarations.incomplete', ['category' => $submission->category->name]).' '.collect($exception->errors())->flatten()->unique()->join(' ')]);
        }
    }
}
