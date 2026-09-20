<?php

namespace Tests\Feature;

use App\Models\Badge;
use App\Models\Child;
use App\Models\ChildExerciseSetting;
use App\Models\ChildFactStat;
use App\Models\DailyGoalLog;
use App\Models\ExerciseType;
use App\Models\Fact;
use App\Models\Family;
use App\Models\PracticeSession;
use App\Models\SessionAttempt;
use App\Models\User;
use App\Services\Gamification\BadgeEvaluator;
use Database\Seeders\BadgeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BadgeTest extends TestCase
{
    use RefreshDatabase;

    private ExerciseType $type;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(BadgeSeeder::class);
        $this->type = ExerciseType::create(['key' => 'multiplication', 'name' => 'Einmaleins']);
    }

    private function makeSession(Child $child, array $attributes = []): PracticeSession
    {
        return PracticeSession::create($attributes + [
            'child_id' => $child->id,
            'exercise_type_id' => $this->type->id,
            'started_at' => now()->subMinutes(10),
            'planned_duration_seconds' => 600,
            'status' => 'completed',
            'questions_answered' => 20,
            'questions_correct' => 18,
        ]);
    }

    private function fact(int $a, int $b): Fact
    {
        return Fact::firstOrCreate(
            ['exercise_type_id' => $this->type->id, 'operand_a' => $a, 'operand_b' => $b],
            ['correct_answer' => $a * $b, 'difficulty_group' => $a],
        );
    }

    private function earnedKeys(Child $child): array
    {
        return $child->badges()->pluck('key')->sort()->values()->all();
    }

    public function test_the_catalogue_is_seeded_idempotently(): void
    {
        $this->assertSame(18, Badge::count());

        $this->seed(BadgeSeeder::class);

        $this->assertSame(18, Badge::count());
    }

    public function test_the_first_real_session_earns_the_first_session_badge_only_once(): void
    {
        $child = Child::factory()->create();
        $evaluator = app(BadgeEvaluator::class);

        $earned = $evaluator->evaluate($child, $this->makeSession($child));
        $this->assertSame(['first_session'], $earned->pluck('key')->all());

        $this->assertTrue($evaluator->evaluate($child, $this->makeSession($child))->isEmpty());
        $this->assertSame(['first_session'], $this->earnedKeys($child));
    }

    public function test_quitting_right_away_does_not_count_as_a_first_session(): void
    {
        $child = Child::factory()->create();

        $earned = app(BadgeEvaluator::class)->evaluate($child, $this->makeSession($child, ['questions_answered' => 2]));

        $this->assertTrue($earned->isEmpty());
    }

    public function test_seven_days_in_a_row_earn_the_streak_badge_counting_todays_session(): void
    {
        $child = Child::factory()->create();

        // Six earlier days met; today's log is not written yet when badges run.
        foreach (range(1, 6) as $daysAgo) {
            DailyGoalLog::create(['child_id' => $child->id, 'date' => now()->subDays($daysAgo)->toDateString(), 'goal_met' => true]);
        }

        $earned = app(BadgeEvaluator::class)->evaluate($child, $this->makeSession($child));

        $this->assertContains('streak_7', $earned->pluck('key')->all());
    }

    public function test_a_gap_or_a_missed_goal_today_breaks_the_streak(): void
    {
        $child = Child::factory()->create();

        // Days 1-3 and 5-6 met, day 4 missing.
        foreach ([1, 2, 3, 5, 6] as $daysAgo) {
            DailyGoalLog::create(['child_id' => $child->id, 'date' => now()->subDays($daysAgo)->toDateString(), 'goal_met' => true]);
        }

        $this->assertNotContains('streak_7', app(BadgeEvaluator::class)->evaluate($child, $this->makeSession($child))->pluck('key')->all());

        // Full six earlier days, but today's session is too short to meet the goal.
        $other = Child::factory()->create();
        foreach (range(1, 6) as $daysAgo) {
            DailyGoalLog::create(['child_id' => $other->id, 'date' => now()->subDays($daysAgo)->toDateString(), 'goal_met' => true]);
        }

        $short = $this->makeSession($other, ['questions_answered' => 6]);
        $this->assertNotContains('streak_7', app(BadgeEvaluator::class)->evaluate($other, $short)->pluck('key')->all());
    }

    private function addAttempts(PracticeSession $session, int $count, int $responseMs, bool $correct = true): void
    {
        foreach (range(1, $count) as $i) {
            SessionAttempt::create([
                'practice_session_id' => $session->id,
                'fact_id' => $this->fact(2, $i)->id,
                'given_answer' => 2 * $i,
                'is_correct' => $correct,
                'response_time_ms' => $responseMs,
                'points_awarded' => 10,
                'question_issued_at' => now(),
                'answered_at' => now(),
            ]);
        }
    }

    public function test_the_blitz_badge_needs_enough_fast_correct_answers(): void
    {
        $fast = Child::factory()->create();
        $session = $this->makeSession($fast);
        $this->addAttempts($session, 10, 1500);
        $this->assertContains('blitz', app(BadgeEvaluator::class)->evaluate($fast, $session)->pluck('key')->all());

        $slow = Child::factory()->create();
        $session = $this->makeSession($slow);
        $this->addAttempts($session, 10, 3000);
        $this->assertNotContains('blitz', app(BadgeEvaluator::class)->evaluate($slow, $session)->pluck('key')->all());

        $few = Child::factory()->create();
        $session = $this->makeSession($few);
        $this->addAttempts($session, 9, 1000);
        $this->assertNotContains('blitz', app(BadgeEvaluator::class)->evaluate($few, $session)->pluck('key')->all());
    }

    public function test_the_blitz_badge_is_never_awarded_when_the_speed_bonus_is_off(): void
    {
        $child = Child::factory()->create();
        ChildExerciseSetting::create([
            'child_id' => $child->id, 'exercise_type_id' => $this->type->id, 'active_groups' => [1],
            'session_duration_minutes' => 10, 'target_frequency' => 'daily', 'speed_bonus_enabled' => false,
        ]);

        $session = $this->makeSession($child);
        $this->addAttempts($session, 10, 500);

        $this->assertNotContains('blitz', app(BadgeEvaluator::class)->evaluate($child, $session)->pluck('key')->all());
    }

    private function practiceRow(Child $child, int $row, int $factsSeen, int $attemptsEach, int $correctEach): void
    {
        foreach (range(1, $factsSeen) as $b) {
            ChildFactStat::create([
                'child_id' => $child->id,
                'fact_id' => $this->fact($row, $b)->id,
                'attempts_total' => $attemptsEach,
                'attempts_correct' => $correctEach,
            ]);
        }
    }

    public function test_row_mastery_needs_attempts_breadth_and_accuracy(): void
    {
        $evaluator = app(BadgeEvaluator::class);

        $master = Child::factory()->create();
        $this->practiceRow($master, 3, 8, 2, 2); // 16 attempts, 100 %, 8 facts
        $this->assertContains('row_mastery_3', $evaluator->evaluate($master, $this->makeSession($master))->pluck('key')->all());

        $narrow = Child::factory()->create();
        $this->practiceRow($narrow, 3, 5, 4, 4); // 20 attempts but only 5 facts
        $this->assertNotContains('row_mastery_3', $evaluator->evaluate($narrow, $this->makeSession($narrow))->pluck('key')->all());

        $sloppy = Child::factory()->create();
        $this->practiceRow($sloppy, 3, 10, 2, 1); // 20 attempts, 50 %
        $this->assertNotContains('row_mastery_3', $evaluator->evaluate($sloppy, $this->makeSession($sloppy))->pluck('key')->all());

        $few = Child::factory()->create();
        $this->practiceRow($few, 3, 8, 1, 1); // only 8 attempts
        $this->assertNotContains('row_mastery_3', $evaluator->evaluate($few, $this->makeSession($few))->pluck('key')->all());

        $this->assertNotContains('row_mastery_4', $evaluator->evaluate($master, $this->makeSession($master))->pluck('key')->all());
    }

    public function test_finishing_a_session_awards_badges_and_the_summary_shows_them_even_after_a_reload(): void
    {
        $child = Child::factory()->create();
        $session = $this->makeSession($child, ['status' => 'active', 'started_at' => now()]);

        $this->actingAs($child, 'child')->post(route('child.sessions.finish', $session))->assertRedirect();

        $this->assertSame(['first_session'], $this->earnedKeys($child));

        foreach ([1, 2] as $visit) {
            $this->actingAs($child, 'child')
                ->get(route('child.sessions.summary', $session))
                ->assertOk()
                ->assertSee('Neues Abzeichen!')
                ->assertSee('Erste Übung');
        }
    }

    public function test_a_badge_from_an_earlier_session_is_not_shown_as_new(): void
    {
        $child = Child::factory()->create();
        $old = Badge::where('key', 'first_session')->first();
        $child->badges()->attach($old->id, ['earned_at' => now()->subDays(3)]);

        $session = $this->makeSession($child, ['started_at' => now()->subMinutes(10)]);

        $this->actingAs($child, 'child')
            ->get(route('child.sessions.summary', $session))
            ->assertOk()
            ->assertDontSee('Neues Abzeichen!');
    }

    public function test_the_child_home_links_to_the_badges_with_a_count_and_the_latest_ones(): void
    {
        $child = Child::factory()->create();
        $child->badges()->attach(Badge::where('key', 'blitz')->first()->id, ['earned_at' => now()]);

        $this->actingAs($child, 'child')
            ->get(route('child.home'))
            ->assertOk()
            ->assertSee('Meine Abzeichen')
            ->assertSee('1 von 12')
            ->assertSee(route('child.achievements'), false)
            ->assertSee('⚡');
    }

    public function test_the_home_invites_to_the_badges_before_the_first_one_when_locked_ones_are_shown(): void
    {
        $child = Child::factory()->create();

        $this->actingAs($child, 'child')->get(route('child.home'))->assertSee('Meine Abzeichen')->assertSee('0 von 12');
    }

    public function test_the_home_hides_the_badge_card_when_nothing_is_earned_and_locked_ones_are_off(): void
    {
        $child = Child::factory()->create(['show_locked_badges' => false]);

        $this->actingAs($child, 'child')->get(route('child.home'))->assertDontSee('Meine Abzeichen');
    }

    public function test_parents_see_all_badges_with_earned_and_locked_ones(): void
    {
        $family = Family::factory()->create();
        $user = User::factory()->for($family)->create();
        $child = Child::factory()->for($family)->create();
        $child->badges()->attach(Badge::where('key', 'first_session')->first()->id, ['earned_at' => '2026-09-01 10:00:00']);

        $this->actingAs($user)
            ->get(route('parent.children.statistics', $child))
            ->assertOk()
            ->assertSee('1 von 12 verdient')
            ->assertSee('Erste Übung')
            ->assertSee('01.09.2026')
            ->assertSee('Meister der 9er-Reihe')
            ->assertSee('filter:grayscale(1)', false);
    }

    public function test_shared_or_specific_artwork_replaces_the_emoji_without_code_changes(): void
    {
        $folder = public_path(self::TEST_BADGE_IMAGES);
        mkdir($folder, 0775, true);
        $shared = $folder.'/row_mastery.png';
        $specific = $folder.'/blitz.png';
        file_put_contents($shared, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='));
        file_put_contents($specific, file_get_contents($shared));

        try {
            $this->assertStringContainsString(self::TEST_BADGE_IMAGES.'/row_mastery.png', Badge::where('key', 'row_mastery_5')->first()->imageUrl());
            $this->assertStringContainsString(self::TEST_BADGE_IMAGES.'/blitz.png', Badge::where('key', 'blitz')->first()->imageUrl());
            $this->assertNull(Badge::where('key', 'first_session')->first()->imageUrl());
            $this->assertSame(5, Badge::where('key', 'row_mastery_5')->first()->rowNumber());
            $this->assertNull(Badge::where('key', 'blitz')->first()->rowNumber());
        } finally {
            @unlink($shared);
            @unlink($specific);
        }
    }

    public function test_tests_never_use_or_touch_the_real_badge_artwork(): void
    {
        // Real artwork lives in public/images/badges. If tests wrote or deleted files
        // there they would destroy it, so the suite works in a throw-away folder.
        $this->assertSame(self::TEST_BADGE_IMAGES, config('badges.images_path'));
        $this->assertNull(Badge::where('key', 'first_session')->first()->imageUrl(), 'emoji fallback, whatever artwork exists in production');
    }
}
