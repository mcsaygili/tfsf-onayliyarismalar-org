<?php

namespace Database\Factories;

use App\Enums\CompetitionAudience;
use App\Enums\CompetitionStatus;
use App\Models\Competition;
use App\Models\Institution;
use App\Models\InstitutionStaff;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Competition>
 */
class CompetitionFactory extends Factory
{
    protected $model = Competition::class;

    public function definition(): array
    {
        $institution = Institution::factory()->create();

        return [
            'institution_id' => $institution->id,
            'institution_staff_id' => InstitutionStaff::factory()->for($institution)->create()->id,
            'audience' => CompetitionAudience::National,
            'name' => fake()->sentence(3),
            'partners' => fake()->sentence(),
            'subject' => fake()->paragraph(),
            'purpose' => fake()->paragraph(),
            'current_step' => 1,
            'status' => CompetitionStatus::Draft,
        ];
    }

    public function pendingReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CompetitionStatus::PendingReview,
            'submitted_at' => now(),
        ]);
    }

    public function needsInfo(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CompetitionStatus::NeedsInfo,
            'submitted_at' => now(),
            'latest_review_message' => fake()->sentence(),
        ]);
    }
}
