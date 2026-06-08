<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemFactory extends Factory
{
    public function definition()
    {
        return [
            'user_id'     => User::factory(),
            'name'        => $this->faker->words(3, true),
            'brand_name'  => $this->faker->optional(0.5)->company(),
            'description' => $this->faker->sentence(),
            'price'       => $this->faker->numberBetween(100, 100000),
            'image_path'  => 'storage/items/dummy.jpg',
            'condition'   => $this->faker->randomElement(['良好', '目立った傷や汚れなし', 'やや傷や汚れあり', '状態が悪い']),
        ];
    }
}
