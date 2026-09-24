<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\ChildExerciseSetting;
use App\Models\ExerciseType;
use App\Models\Fact;
use App\Models\PracticeSession;
use Database\Seeders\ExerciseTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class PerExercisePointsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ExerciseTypeSeeder::class);
    }

    private function typeId(string $key): int
    {
        return ExerciseType::where('key', $key)->value('id');
    }

    private function setting(Child $child, string $key): ChildExerciseSetting
    {
        return ChildExerciseSetting::create([
            'child_id' => $child->id, 'exercise_type_id' => $this->typeId($key), 'enabled' => true,
            'active_groups' => [1], 'session_duration_minutes' => 10, 'target_frequency' => 'daily',
        ]);
    }

    public function test_a_correct_answer_adds_points_to_the_childs_total_and_to_the_exercises_own_points(): void
    {
        $child = Child::factory()->create();
        $this->setting($child, 'multiplication');
        $this->actingAs($child, 'child')->post(route('child.sessions.start'));
        $session = PracticeSession::first();
        $this->actingAs($child, 'child')->getJson(route('child.sessions.next-question', $session));
        $fact = $session->refresh()->currentFact;

        $response = $this->actingAs($child, 'child')->postJson(route('child.sessions.attempts', $session), ['answer' => $fact->correct_answer]);

        $points = $response->json('points_awarded');
        $this->assertGreaterThan(0, $points);
        $this->assertSame($points, $child->refresh()->total_points);
        $this->assertSame($points, $child->exerciseSettings()->where('exercise_type_id', $this->typeId('multiplication'))->value('points'));
    }

    public function test_a_wrong_answer_adds_no_points_anywhere(): void
    {
        $child = Child::factory()->create();
        $setting = $this->setting($child, 'multiplication');
        $this->actingAs($child, 'child')->post(route('child.sessions.start'));
        $session = PracticeSession::first();
        $this->actingAs($child, 'child')->getJson(route('child.sessions.next-question', $session));
        $fact = $session->refresh()->currentFact;

        $this->actingAs($child, 'child')->postJson(route('child.sessions.attempts', $session), ['answer' => $fact->correct_answer + 1]);

        $this->assertSame(0, $child->refresh()->total_points);
        $this->assertSame(0, $setting->refresh()->points);
    }

    public function test_two_exercises_accumulate_points_independently(): void
    {
        $child = Child::factory()->create();
        $this->setting($child, 'multiplication');
        $this->setting($child, 'addition');

        $answer = function (string $exerciseKey) use ($child) {
            $this->actingAs($child, 'child')->post(route('child.sessions.start'), ['exercise' => $exerciseKey]);
            $session = PracticeSession::where('exercise_type_id', $this->typeId($exerciseKey))->latest('id')->first();
            $this->actingAs($child, 'child')->getJson(route('child.sessions.next-question', $session));
            $fact = $session->refresh()->currentFact;

            return $this->actingAs($child, 'child')->postJson(route('child.sessions.attempts', $session), ['answer' => $fact->correct_answer])->json('points_awarded');
        };

        $multiplicationPoints = $answer('multiplication');
        $additionPoints = $answer('addition');

        $this->assertSame($multiplicationPoints, $child->exerciseSettings()->where('exercise_type_id', $this->typeId('multiplication'))->value('points'));
        $this->assertSame($additionPoints, $child->exerciseSettings()->where('exercise_type_id', $this->typeId('addition'))->value('points'));
        $this->assertSame($multiplicationPoints + $additionPoints, $child->refresh()->total_points);
    }

    public function test_the_level_of_an_exercise_is_derived_from_its_own_points(): void
    {
        $child = Child::factory()->create();
        $setting = $this->setting($child, 'multiplication');
        $setting->update(['points' => 1000]);

        $this->assertSame(2, $setting->level()['level']);
    }

    public function test_the_backfill_migration_reconstructs_points_per_exercise_from_session_history(): void
    {
        $child = Child::factory()->create(['total_points' => 700]);
        $multiplicationSetting = $this->setting($child, 'multiplication');
        $additionSetting = $this->setting($child, 'addition');

        $type = ExerciseType::first();
        $fact = Fact::where('exercise_type_id', $type->id)->first();
        $makeSession = fn (int $exerciseTypeId, int $points) => PracticeSession::create([
            'child_id' => $child->id, 'exercise_type_id' => $exerciseTypeId, 'started_at' => now(),
            'planned_duration_seconds' => 600, 'status' => 'completed', 'total_points' => $points,
        ]);
        // Two sessions of the same exercise must add up, not overwrite.
        $makeSession($this->typeId('multiplication'), 300);
        $makeSession($this->typeId('multiplication'), 200);
        $makeSession($this->typeId('addition'), 200);

        (require database_path('migrations/2026_09_24_100001_backfill_child_exercise_settings_points.php'))->up();

        $this->assertSame(500, $multiplicationSetting->refresh()->points);
        $this->assertSame(200, $additionSetting->refresh()->points);
    }

    public function test_the_backfill_migration_is_safe_to_run_twice(): void
    {
        $child = Child::factory()->create();
        $setting = $this->setting($child, 'multiplication');
        PracticeSession::create([
            'child_id' => $child->id, 'exercise_type_id' => $this->typeId('multiplication'), 'started_at' => now(),
            'planned_duration_seconds' => 600, 'status' => 'completed', 'total_points' => 150,
        ]);

        $migration = require database_path('migrations/2026_09_24_100001_backfill_child_exercise_settings_points.php');
        $migration->up();
        $migration->up();

        $this->assertSame(150, $setting->refresh()->points);
    }

    public function test_the_backfill_migration_logs_a_warning_when_totals_do_not_reconcile_but_does_not_fail(): void
    {
        // A child with no exercise settings at all has nothing to backfill into,
        // so their (non-zero) total_points can never be reconciled — this must
        // not throw, only be logged for someone to look at.
        Child::factory()->create(['total_points' => 999]);

        Log::shouldReceive('warning')->once();

        (require database_path('migrations/2026_09_24_100001_backfill_child_exercise_settings_points.php'))->up();
    }
}
