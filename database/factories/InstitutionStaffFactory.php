<?php

namespace Database\Factories;

use App\Models\Institution;
use App\Models\InstitutionStaff;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<InstitutionStaff>
 */
class InstitutionStaffFactory extends Factory
{
    protected $model = InstitutionStaff::class;

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'status' => true,
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
