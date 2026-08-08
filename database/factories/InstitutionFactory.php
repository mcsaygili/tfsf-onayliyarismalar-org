<?php

namespace Database\Factories;

use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Institution>
 */
class InstitutionFactory extends Factory
{
    protected $model = Institution::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'email' => fake()->unique()->companyEmail(),
            'status' => true,
            'approved_at' => now(),
        ];
    }

    /**
     * Henüz onaylanmamış (bkz. Institution::isApproved()).
     */
    public function unapproved(): static
    {
        return $this->state(fn (array $attributes) => [
            'approved_at' => null,
        ]);
    }
}
