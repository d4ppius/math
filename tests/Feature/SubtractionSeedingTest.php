<?php

namespace Tests\Feature;

use App\Models\ExerciseType;
use Database\Seeders\ExerciseTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubtractionSeedingTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_seeder_creates_the_subtraction_type_with_its_tasks_and_can_run_again(): void
    {
        $this->seed(ExerciseTypeSeeder::class);
        $this->seed(ExerciseTypeSeeder::class);

        $type = ExerciseType::where('key', 'subtraction')->firstOrFail();

        $this->assertSame('Minus bis 20', $type->name);
        $this->assertTrue($type->is_active);
        $this->assertSame(110, $type->facts()->count());
        $this->assertSame(45, $type->facts()->where('difficulty_group', 1)->count());
        $this->assertSame(5, $type->facts()->where('operand_a', 13)->where('operand_b', 8)->value('correct_answer'));
        // The other exercises are untouched.
        $this->assertSame(90, ExerciseType::where('key', 'multiplication')->firstOrFail()->facts()->count());
        $this->assertSame(100, ExerciseType::where('key', 'addition')->firstOrFail()->facts()->count());
    }
}
