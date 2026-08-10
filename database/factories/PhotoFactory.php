<?php

namespace Database\Factories;

use App\Models\Photo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Photo>
 */
class PhotoFactory extends Factory
{
    protected $model = Photo::class;

    public function definition(): array
    {
        $uuid = fake()->uuid();

        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(3),
            'location' => fake()->city(),
            'taken_at' => fake()->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'tags' => fake()->words(3),
            'tags_text' => implode(' ', fake()->words(3)),
            'description' => fake()->sentence(),
            'disk_path' => "portfolio/test/{$uuid}.jpg",
            'thumb_path' => "portfolio/test/{$uuid}_thumb.jpg",
            'original_filename' => "{$uuid}.jpg",
            'mime_type' => 'image/jpeg',
            'file_size_bytes' => fake()->numberBetween(50_000, 5_000_000),
            'width' => 1920,
            'height' => 1080,
            'exif_missing' => false,
            'camera_make' => 'Canon',
            'camera_model' => 'EOS R5',
        ];
    }

    public function withoutExif(): static
    {
        return $this->state(fn (array $attributes) => [
            'exif_missing' => true,
            'camera_make' => null,
            'camera_model' => null,
            'lens' => null,
            'focal_length' => null,
            'aperture' => null,
            'shutter_speed' => null,
            'iso' => null,
            'exif_captured_at' => null,
        ]);
    }
}
