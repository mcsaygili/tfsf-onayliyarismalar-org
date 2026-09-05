<?php

namespace Tests\Concerns;

use App\Enums\CompetitionAudience;
use App\Enums\CompetitionStatus;
use App\Models\CaptureDevice;
use App\Models\Competition;
use App\Models\CompetitionEntry;
use App\Models\CompetitionSubmission;
use App\Models\ProcessingMethod;
use App\Models\User;
use Database\Seeders\CompetitionCategoryReferenceSeeder;

trait CreatesSecuritySubmission
{
    private function securitySubmission(): CompetitionSubmission
    {
        $this->seed(CompetitionCategoryReferenceSeeder::class);
        $competition = Competition::factory()->create([
            'audience' => CompetitionAudience::International,
            'status' => CompetitionStatus::Approved,
            'published_at' => now()->subDay(),
            'application_starts_at' => now()->subDay(),
            'application_ends_at' => now()->addDay(),
        ]);
        $category = $competition->categories()->create(['sort_order' => 10, 'max_photos_per_participant' => 4]);
        $category->captureDevices()->sync([CaptureDevice::where('code', 'no-device-check')->value('id')]);
        $category->processingMethods()->sync([ProcessingMethod::where('code', 'no-processing-check')->value('id')]);
        $entry = CompetitionEntry::create(['competition_id' => $competition->id, 'user_id' => User::factory()->create()->id, 'status' => 'draft']);

        return $entry->submissions()->create(['competition_category_id' => $category->id, 'status' => 'draft']);
    }
}
