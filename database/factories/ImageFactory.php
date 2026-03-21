<?php

namespace Database\Factories;

use App\Models\Image;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ImageFactory extends Factory
{
    protected $model = Image::class;

    public function definition(): array
    {
        return [
            'disk' => 'public', // or 'imagekit' if you want to simulate that
            'name' => $this->faker->word().'.jpg',
            'filepath' => 'images/'.Str::random(10).'.jpg',
            'mimetype' => 'image/jpeg',
            'width' => $this->faker->numberBetween(200, 2000),
            'height' => $this->faker->numberBetween(200, 2000),
            'filesize' => $this->faker->numberBetween(10_000, 5_000_000), // bytes
            'owner_type' => null,
            'owner_id' => null,
            'creator_type' => null,
            'creator_id' => null,
        ];
    }
}
