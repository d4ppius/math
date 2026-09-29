<?php

namespace Tests\Feature;

use App\Models\Badge;
use App\Models\Child;
use App\Models\ChildExerciseSetting;
use App\Models\ExerciseType;
use App\Models\PracticeSession;
use App\Services\Gamification\BadgeEvaluator;
use Database\Seeders\BadgeSeeder;
use Database\Seeders\ExerciseTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LevelBadgesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ExerciseTypeSeeder::class);
        $this->seed(BadgeSeeder::class);
    }

    private function typeId(string $key): int
    {
        return ExerciseType::where('key', $key)->value('id');
    }

    /** A child whose given exercise already holds enough points for a level. */
    private function childAtPoints(string $key, int $points): Child
    {
        $child = Child::factory()->create();
        ChildExerciseSetting::create([
            'child_id' => $child->id, 'exercise_type_id' => $this->typeId($key), 'enabled' => true,
            'active_groups' => [1], 'session_duration_minutes' => 10, 'target_frequency' => 'daily', 'points' => $points,
        ]);

        return $child;
    }

    private function evaluate(Child $child): array
    {
        $session = PracticeSession::create([
            'child_id' => $child->id, 'exercise_type_id' => $this->typeId('multiplication'), 'started_at' => now(),
            'planned_duration_seconds' => 600, 'status' => 'completed', 'questions_answered' => 20, 'questions_correct' => 18,
        ]);

        return app(BadgeEvaluator::class)->evaluate($child, $session)->pluck('key')->all();
    }

    public function test_the_catalogue_has_a_badge_per_level_two_to_ten(): void
    {
        foreach (range(2, 10) as $level) {
            $this->assertNotNull(Badge::where('key', "level_{$level}")->first());
        }

        $this->assertSame('Zahlen-Entdecker', Badge::where('key', 'level_2')->first()->name);
        $this->assertSame('🦊', Badge::where('key', 'level_10')->first()->icon);
    }

    public function test_reaching_a_level_earns_that_and_every_lower_level_badge_at_once(): void
    {
        // 1650 points is level 3, so levels 2 and 3 are both reached.
        $child = $this->childAtPoints('multiplication', 1650);

        $earned = $this->evaluate($child);

        $this->assertContains('level_2', $earned);
        $this->assertContains('level_3', $earned);
        $this->assertNotContains('level_4', $earned);
    }

    public function test_a_fresh_child_earns_no_level_badge(): void
    {
        $child = $this->childAtPoints('multiplication', 0); // level 1

        $this->assertEmpty(array_filter($this->evaluate($child), fn ($key) => str_starts_with($key, 'level_')));
    }

    public function test_the_highest_level_across_any_exercise_counts(): void
    {
        $child = $this->childAtPoints('multiplication', 100); // level 1
        ChildExerciseSetting::create([
            'child_id' => $child->id, 'exercise_type_id' => $this->typeId('addition'), 'enabled' => true,
            'active_groups' => [1], 'session_duration_minutes' => 10, 'target_frequency' => 'daily', 'points' => 3800, // level 4
        ]);

        $earned = $this->evaluate($child);

        $this->assertContains('level_4', $earned);
        $this->assertNotContains('level_5', $earned);
    }

    public function test_level_badges_are_attainable_for_every_child(): void
    {
        // No exercise settings at all: level badges are general, so they still show as goals.
        $child = Child::factory()->create();

        $this->assertTrue($child->attainableBadges()->contains(fn ($badge) => $badge->key === 'level_5'));
    }
}
