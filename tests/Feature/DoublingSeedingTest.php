<?php

namespace Tests\Feature;

use App\Models\ExerciseType;
use Database\Seeders\ExerciseTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoublingSeedingTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_seeder_creates_the_doubling_type_with_its_tasks_and_can_run_again(): void
    {
        $this->seed(ExerciseTypeSeeder::class);
        $this->seed(ExerciseTypeSeeder::class);

        $type = ExerciseType::where('key', 'doubling')->firstOrFail();

        $this->assertSame('Verdoppeln & Halbieren', $type->name);
        $this->assertTrue($type->is_active);
        $this->assertSame(50, $type->facts()->count());
        // Doubling 7 and halving 8 sit on distinct keys and hold their own answers.
        $this->assertSame(14, $type->facts()->where('operand_a', 7)->where('operand_b', 2)->value('correct_answer'));
        $this->assertSame(4, $type->facts()->where('operand_a', 8)->where('operand_b', 0)->value('correct_answer'));
        // The other exercises are untouched.
        $this->assertSame(90, ExerciseType::where('key', 'multiplication')->firstOrFail()->facts()->count());
        $this->assertSame(110, ExerciseType::where('key', 'subtraction')->firstOrFail()->facts()->count());
    }
}
