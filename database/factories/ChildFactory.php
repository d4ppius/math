<?php

namespace Database\Factories;

use App\Models\Child;
use App\Models\Family;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Child>
 */
class ChildFactory extends Factory
{
    protected $model = Child::class;

    public function definition(): array
    {
        return [
            'family_id' => Family::factory(),
            'name' => fake()->firstName(),
            'avatar' => fake()->randomElement(['fox', 'owl', 'cat', 'bear', 'rabbit', 'panda']),
            'color_theme' => fake()->randomElement(['orange', 'blue', 'green', 'pink', 'purple']),
            'login_token_hash' => hash('sha256', Str::random(48)),
            'active' => true,
            'total_points' => 0,
        ];
    }
}
