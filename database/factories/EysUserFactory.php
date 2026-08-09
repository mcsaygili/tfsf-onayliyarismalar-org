<?php

namespace Database\Factories;

use App\Models\EysUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<EysUser>
 */
class EysUserFactory extends Factory
{
    protected $model = EysUser::class;

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'status' => true,
            'remember_token' => Str::random(10),
        ];
    }
}
