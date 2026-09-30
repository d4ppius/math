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

class SubtractionBadgesTest extends TestCase
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

    private function setting(Child $child, string $key, bool $enabled = true, bool $speedBonus = true): void
    {
        ChildExerciseSetting::create([
            'child_id' => $child->id, 'exercise_type_id' => $this->typeId($key), 'enabled' => $enabled,
            'active_groups' => [1], 'session_duration_minutes' => 10, 'target_frequency' => 'daily', 'speed_bonus_enabled' => $speedBonus,
        ]);
    }

    private function makeSession(Child $child, string $key, array $attributes = []): PracticeSession
    {
        return PracticeSession::create($attributes + [
            'child_id' => $child->id, 'exercise_type_id' => $this->typeId($key), 'started_at' => now()->subMinutes(10),
            'planned_duration_seconds' => 600, 'status' => 'completed', 'questions_answered' => 20, 'questions_correct' => 18,
        ]);
    }

    private function evaluate(Child $child, PracticeSession $session): array
    {
        return array_values(array_filter(app(BadgeEvaluator::class)->evaluate($child, $session)->pluck('key')->all(), fn ($key) => ! str_starts_with($key, 'weekday_')));
    }

    private function practiceGroup(Child $child, int $group, int $factsSeen, int $attemptsEach, int $correctEach): void
    {
        $facts = Fact::where('exercise_type_id', $this->typeId('subtraction'))->where('difficulty_group', $group)->limit($factsSeen)->get();

        foreach ($facts as $fact) {
            ChildFactStat::create(['child_id' => $child->id, 'fact_id' => $fact->id, 'attempts_total' => $attemptsEach, 'attempts_correct' => $correctEach]);
        }
    }

    private function fastAttempts(PracticeSession $session, int $count, int $ms): void
    {
        $facts = Fact::where('exercise_type_id', $this->typeId('subtraction'))->limit($count)->get();

        foreach ($facts as $fact) {
            SessionAttempt::create([
                'practice_session_id' => $session->id, 'fact_id' => $fact->id, 'given_answer' => $fact->correct_answer, 'is_correct' => true,
                'response_time_ms' => $ms, 'points_awarded' => 10, 'question_issued_at' => now(), 'answered_at' => now(),
            ]);
        }
    }

    public function test_the_catalogue_contains_the_subtraction_badges(): void
    {
        $keys = Badge::pluck('key')->all();

        foreach (['subtraction_first_session', 'subtraction_mastery_1', 'subtraction_mastery_2', 'subtraction_mastery_3', 'subtraction_blitz'] as $key) {
            $this->assertContains($key, $keys);
        }

        $this->assertSame('subtraction', Badge::where('key', 'subtraction_blitz')->first()->exerciseKey());
        $this->assertSame('20', Badge::where('key', 'subtraction_mastery_3')->first()->overlay());
    }

    public function test_the_minus_starter_needs_a_minus_session(): void
    {
        $child = Child::factory()->create();
        $this->setting($child, 'subtraction');

        $this->assertEqualsCanonicalizing(
            ['first_session', 'subtraction_first_session'],
            $this->evaluate($child, $this->makeSession($child, 'subtraction')),
        );

        // A short session does not earn the starter.
        $other = Child::factory()->create();
        $this->setting($other, 'subtraction');
        $this->assertNotContains('subtraction_first_session', $this->evaluate($other, $this->makeSession($other, 'subtraction', ['questions_answered' => 3])));
    }

    public function test_a_minus_group_master_needs_breadth_and_accuracy(): void
    {
        $master = Child::factory()->create();
        $this->setting($master, 'subtraction');
        $this->practiceGroup($master, 1, 34, 2, 2); // 68 attempts, 34 facts, 100 %
        $this->assertContains('subtraction_mastery_1', $this->evaluate($master, $this->makeSession($master, 'subtraction')));

        $sloppy = Child::factory()->create();
        $this->setting($sloppy, 'subtraction');
        $this->practiceGroup($sloppy, 1, 34, 2, 1); // only 50 % correct
        $this->assertNotContains('subtraction_mastery_1', $this->evaluate($sloppy, $this->makeSession($sloppy, 'subtraction')));
    }

    public function test_the_minus_blitz_depends_on_the_speed_bonus_toggle(): void
    {
        $withBonus = Child::factory()->create();
        $this->setting($withBonus, 'subtraction', speedBonus: true);
        $session = $this->makeSession($withBonus, 'subtraction');
        $this->fastAttempts($session, 10, 1200);
        $this->assertContains('subtraction_blitz', $this->evaluate($withBonus, $session));

        $noBonus = Child::factory()->create();
        $this->setting($noBonus, 'subtraction', speedBonus: false);
        $session = $this->makeSession($noBonus, 'subtraction');
        $this->fastAttempts($session, 10, 1200);
        $this->assertNotContains('subtraction_blitz', $this->evaluate($noBonus, $session));
    }

    public function test_minus_counts_as_a_second_exercise_for_the_allrounder(): void
    {
        $child = Child::factory()->create();
        $this->setting($child, 'multiplication');
        $this->setting($child, 'subtraction');

        $this->makeSession($child, 'multiplication', ['started_at' => now()->subHours(2)]);
        $second = $this->makeSession($child, 'subtraction', ['started_at' => now()->subHour()]);

        $this->assertContains('allrounder', $this->evaluate($child, $second));
    }

    public function test_subtraction_badges_are_only_attainable_when_minus_is_enabled(): void
    {
        $child = Child::factory()->create();
        $this->setting($child, 'multiplication');
        $this->setting($child, 'subtraction', enabled: false);

        $this->actingAs($child, 'child')->get(route('child.achievements'))->assertOk()->assertDontSee('Minus-Starter');

        $child->exerciseSettings()->where('exercise_type_id', $this->typeId('subtraction'))->update(['enabled' => true]);
        $this->actingAs($child->fresh(), 'child')->get(route('child.achievements'))->assertOk()->assertSee('Minus-Starter');
    }
}
