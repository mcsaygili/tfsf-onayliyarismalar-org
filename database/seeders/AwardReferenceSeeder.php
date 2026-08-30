<?php

namespace Database\Seeders;

use App\Models\AwardReference;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AwardReferenceSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/award_references.csv');
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

        foreach (array_slice($lines, 1) as $line) {
            [$id, $sortOrder, $kind, $status, $trName, $trDescription, $enName, $enDescription] = array_pad(str_getcsv($line, '|'), 8, '');
            $code = Str::slug($enName ?: $trName);

            $award = AwardReference::withTrashed()->find($id) ?? new AwardReference(['id' => $id]);
            $award->forceFill([
                'code' => $code,
                'kind' => $kind,
                'sort_order' => (int) $sortOrder,
                'status' => (bool) $status,
                'is_system' => true,
            ])->save();

            if ($award->trashed()) {
                $award->restore();
            }

            $award->upsertTranslations([
                'tr' => ['name' => $trName, 'description' => $trDescription ?: null],
                'en' => ['name' => $enName, 'description' => $enDescription ?: null],
            ]);
        }
    }
}
