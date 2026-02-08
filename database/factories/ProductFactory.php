<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'description' => $this->faker->sentence(),
            'price' => $this->faker->randomFloat(2, 1, 100),
            'calories' => $this->faker->numberBetween(50, 600),
            'protein_g' => $this->faker->randomFloat(2, 1, 50),
            'carbs_g' => $this->faker->randomFloat(2, 1, 100),
            'fat_g' => $this->faker->randomFloat(2, 1, 50),
            'stock' => $this->faker->numberBetween(0, 100),
            'is_active' => true,
        ];
    }
}
