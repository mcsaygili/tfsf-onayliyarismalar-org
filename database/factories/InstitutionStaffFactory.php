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
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'status' => true,
            'remember_token' => Str::random(10),
        ];
    }
}
