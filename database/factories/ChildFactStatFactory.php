<?php

namespace Database\Factories;

use App\Models\Child;
use App\Models\ChildFactStat;
use App\Models\Fact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChildFactStat>
 */
class ChildFactStatFactory extends Factory
{
    protected $model = ChildFactStat::class;

    public function definition(): array
    {
        return [
            'child_id' => Child::factory(),
            'fact_id' => Fact::factory(),
            'attempts_total' => 0,
            'attempts_correct' => 0,
            'avg_response_ms' => 0,
            'current_streak' => 0,
            'priority_score' => 1,
        ];
    }
}
