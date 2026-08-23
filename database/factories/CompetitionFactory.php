<?php

namespace Database\Factories;

use App\Enums\CompetitionAudience;
use App\Enums\CompetitionInfrastructureProvider;
use App\Enums\CompetitionStatus;
use App\Models\Competition;
use App\Models\CompetitionType;
use App\Models\Institution;
use App\Models\InstitutionStaff;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Competition>
 */
class CompetitionFactory extends Factory
{
    protected $model = Competition::class;

    public function configure(): static
    {
        return $this->afterCreating(function (Competition $competition) {
            $translations = [
                'tr' => [
                    'name' => fake()->sentence(3),
                    'subject' => fake()->paragraph(),
                    'purpose' => fake()->paragraph(),
                ],
            ];

            if ($competition->requiresEnglishContent()) {
                $translations['en'] = [
                    'name' => fake()->sentence(3),
                    'subject' => fake()->paragraph(),
                    'purpose' => fake()->paragraph(),
                ];
            }

            $competition->upsertTranslations($translations);
        });
    }

    public function definition(): array
    {
        $institution = Institution::factory()->create();

        return [
            'institution_id' => $institution->id,
            'institution_staff_id' => InstitutionStaff::factory()->for($institution)->create()->id,
            'audience' => CompetitionAudience::National,
            'infrastructure_provider' => CompetitionInfrastructureProvider::Tfsf,
            'competition_type_id' => CompetitionType::factory(),
            'country_id' => null,
            'city_id' => null,
            'participant_approval_process_id' => null,
            'partners' => fake()->sentence(),
            'current_step' => 1,
            'status' => CompetitionStatus::Draft,
        ];
    }

    /** @param array<string, array<string, mixed>> $translations */
    public function withTranslations(array $translations): static
    {
        return $this->afterCreating(
            fn (Competition $competition) => $competition->upsertTranslations($translations)
        );
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
