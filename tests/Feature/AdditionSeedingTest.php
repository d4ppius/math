<?php

namespace Tests\Feature;

use App\Models\ExerciseType;
use App\Services\ExerciseTypes\AdditionExerciseType;
use Database\Seeders\ExerciseTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdditionSeedingTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_seeder_creates_the_addition_type_with_its_hundred_tasks_and_can_run_again(): void
    {
        config(['exercise_types.addition' => AdditionExerciseType::class]);

        $this->seed(ExerciseTypeSeeder::class);
        $this->seed(ExerciseTypeSeeder::class);

        $type = ExerciseType::where('key', 'addition')->firstOrFail();

        $this->assertSame('Plus bis 20', $type->name);
        $this->assertTrue($type->is_active);
        $this->assertSame(100, $type->facts()->count());
        $this->assertSame(45, $type->facts()->where('difficulty_group', 1)->count());
        $this->assertSame(15, $type->facts()->where('operand_a', 7)->where('operand_b', 8)->value('correct_answer'));
        // Einmaleins is untouched.
        $this->assertSame(90, ExerciseType::where('key', 'multiplication')->firstOrFail()->facts()->count());
    }
}
