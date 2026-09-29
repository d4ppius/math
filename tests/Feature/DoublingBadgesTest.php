<?php

namespace Tests\Feature;

use App\Models\Badge;
use App\Models\Child;
use App\Models\ChildExerciseSetting;
use App\Models\ChildFactStat;
use App\Models\ExerciseType;
use App\Models\Fact;
use App\Models\PracticeSession;
use App\Models\SessionAttempt;
use App\Services\Gamification\BadgeEvaluator;
use Database\Seeders\BadgeSeeder;
use Database\Seeders\ExerciseTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoublingBadgesTest extends TestCase
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

    private function setting(Child $child, string $key, bool $speedBonus = true): void
    {
        ChildExerciseSetting::create([
            'child_id' => $child->id, 'exercise_type_id' => $this->typeId($key), 'enabled' => true,
            'active_groups' => [1, 2, 3, 4], 'session_duration_minutes' => 10, 'target_frequency' => 'daily', 'speed_bonus_enabled' => $speedBonus,
        ]);
    }

    private function makeSession(Child $child, array $attributes = []): PracticeSession
    {
        return PracticeSession::create($attributes + [
            'child_id' => $child->id, 'exercise_type_id' => $this->typeId('doubling'), 'started_at' => now()->subMinutes(10),
            'planned_duration_seconds' => 600, 'status' => 'completed', 'questions_answered' => 20, 'questions_correct' => 18,
        ]);
    }

    private function evaluate(Child $child, PracticeSession $session): array
    {
        return app(BadgeEvaluator::class)->evaluate($child, $session)->pluck('key')->all();
    }

    /** Practise facts of the given difficulty groups, all fully correct. */
    private function practiceGroups(Child $child, array $groups, int $factsSeen): void
    {
        $facts = Fact::where('exercise_type_id', $this->typeId('doubling'))
            ->whereIn('difficulty_group', $groups)->limit($factsSeen)->get();

        foreach ($facts as $fact) {
            ChildFactStat::create(['child_id' => $child->id, 'fact_id' => $fact->id, 'attempts_total' => 2, 'attempts_correct' => 2]);
        }
    }

    public function test_the_catalogue_contains_the_doubling_badges(): void
    {
        $keys = Badge::pluck('key')->all();

        foreach (['doubling_first_session', 'doubling_mastery', 'halving_mastery', 'doubling_blitz'] as $key) {
            $this->assertContains($key, $keys);
        }

        $this->assertSame('·2', Badge::where('key', 'doubling_mastery')->first()->overlay());
        $this->assertSame('½', Badge::where('key', 'halving_mastery')->first()->overlay());
    }

    public function test_the_starter_needs_a_doubling_session(): void
    {
        $child = Child::factory()->create();
        $this->setting($child, 'doubling');

        $this->assertEqualsCanonicalizing(
            ['first_session', 'doubling_first_session'],
            $this->evaluate($child, $this->makeSession($child)),
        );
    }

    public function test_the_doubling_master_spans_both_number_ranges(): void
    {
        // Only the "bis 10" range practised (10 facts): not enough breadth yet.
        $partial = Child::factory()->create();
        $this->setting($partial, 'doubling');
        $this->practiceGroups($partial, [1], 10);
        $this->assertNotContains('doubling_mastery', $this->evaluate($partial, $this->makeSession($partial)));

        // Both doubling ranges practised: the master badge is earned, and it is
        // the doubling one, not the halving one (different difficulty groups).
        $master = Child::factory()->create();
        $this->setting($master, 'doubling');
        $this->practiceGroups($master, [1, 2], 20);
        $earned = $this->evaluate($master, $this->makeSession($master));
        $this->assertContains('doubling_mastery', $earned);
        $this->assertNotContains('halving_mastery', $earned);
    }

    public function test_the_halving_master_is_separate_from_the_doubling_one(): void
    {
        $child = Child::factory()->create();
        $this->setting($child, 'doubling');
        $this->practiceGroups($child, [3, 4], 20);

        $earned = $this->evaluate($child, $this->makeSession($child));
        $this->assertContains('halving_mastery', $earned);
        $this->assertNotContains('doubling_mastery', $earned);
    }

    public function test_the_blitz_depends_on_the_speed_bonus_toggle(): void
    {
        $child = Child::factory()->create();
        $this->setting($child, 'doubling', speedBonus: false);
        $session = $this->makeSession($child);

        $facts = Fact::where('exercise_type_id', $this->typeId('doubling'))->limit(10)->get();
        foreach ($facts as $fact) {
            SessionAttempt::create([
                'practice_session_id' => $session->id, 'fact_id' => $fact->id, 'given_answer' => $fact->correct_answer, 'is_correct' => true,
                'response_time_ms' => 1000, 'points_awarded' => 10, 'question_issued_at' => now(), 'answered_at' => now(),
            ]);
        }

        $this->assertNotContains('doubling_blitz', $this->evaluate($child, $session));
    }

    public function test_the_existing_single_group_masteries_still_work(): void
    {
        // Regression: the group_mastery extension must not break the Plus badges.
        $child = Child::factory()->create();
        ChildExerciseSetting::create([
            'child_id' => $child->id, 'exercise_type_id' => $this->typeId('addition'), 'enabled' => true,
            'active_groups' => [1], 'session_duration_minutes' => 10, 'target_frequency' => 'daily',
        ]);
        $facts = Fact::where('exercise_type_id', $this->typeId('addition'))->where('difficulty_group', 1)->limit(34)->get();
        foreach ($facts as $fact) {
            ChildFactStat::create(['child_id' => $child->id, 'fact_id' => $fact->id, 'attempts_total' => 2, 'attempts_correct' => 2]);
        }

        $session = PracticeSession::create([
            'child_id' => $child->id, 'exercise_type_id' => $this->typeId('addition'), 'started_at' => now()->subMinutes(10),
            'planned_duration_seconds' => 600, 'status' => 'completed', 'questions_answered' => 20, 'questions_correct' => 20,
        ]);

        $this->assertContains('addition_mastery_1', $this->evaluate($child, $session));
    }
}
