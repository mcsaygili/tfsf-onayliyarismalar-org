<?php

namespace Database\Factories;

use App\Models\CompetitionType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CompetitionType>
 */
class CompetitionTypeFactory extends Factory
{
    protected $model = CompetitionType::class;

    public function configure(): static
    {
        return $this->afterCreating(function (CompetitionType $competitionType) {
            if ($competitionType->code === 'photographers-marathon') {
                $competitionType->update(['requires_location' => true, 'requires_approval_process' => true]);
            }

            $name = fake()->unique()->words(3, true);

            $competitionType->upsertTranslations([
                'tr' => ['name' => Str::title($name), 'description' => fake()->sentence()],
                'en' => ['name' => Str::title($name), 'description' => fake()->sentence()],
            ]);
        });
    }

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->slug(3),
            'sort_order' => fake()->numberBetween(1, 100),
            'status' => true,
        ];
    }
}
