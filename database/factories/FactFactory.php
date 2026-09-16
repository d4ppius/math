<?php

namespace Database\Factories;

use App\Models\ExerciseType;
use App\Models\Fact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fact>
 */
class FactFactory extends Factory
{
    protected $model = Fact::class;

    public function definition(): array
    {
        $a = fake()->numberBetween(1, 9);
        $b = fake()->numberBetween(1, 10);

        return [
            'exercise_type_id' => ExerciseType::factory(),
            'operand_a' => $a,
            'operand_b' => $b,
            'correct_answer' => $a * $b,
            'difficulty_group' => $a,
        ];
    }
}
