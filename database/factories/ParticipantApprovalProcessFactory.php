<?php

namespace Database\Factories;

use App\Models\ParticipantApprovalProcess;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ParticipantApprovalProcess>
 */
class ParticipantApprovalProcessFactory extends Factory
{
    protected $model = ParticipantApprovalProcess::class;

    public function configure(): static
    {
        return $this->afterCreating(function (ParticipantApprovalProcess $process) {
            $name = fake()->unique()->words(2, true);

            $process->upsertTranslations([
                'tr' => ['name' => Str::title($name), 'description' => fake()->sentence()],
                'en' => ['name' => Str::title($name), 'description' => fake()->sentence()],
            ]);
        });
    }

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->slug(2),
            'sort_order' => fake()->numberBetween(1, 100),
            'status' => true,
        ];
    }
}
