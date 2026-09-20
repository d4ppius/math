<?php

namespace Tests\Feature;

use App\Models\Badge;
use App\Models\Child;
use App\Models\ChildExerciseSetting;
use App\Models\ChildFactStat;
use App\Models\ExerciseType;
use App\Models\Fact;
use App\Models\Family;
use App\Models\PracticeSession;
use App\Models\SessionAttempt;
use App\Models\User;
use App\Services\Gamification\BadgeEvaluator;
use App\Services\Gamification\BadgeSections;
use Database\Seeders\BadgeSeeder;
use Database\Seeders\ExerciseTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlusBadgesTest extends TestCase
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

    private function childWith(bool $plus = true, bool $plusSpeedBonus = true): Child
    {
        $child = Child::factory()->create();
        $this->setting($child, 'multiplication');
        $this->setting($child, 'addition', $plus, $plusSpeedBonus);

        return $child;
    }

    private function makeSession(Child $child, string $key, array $attributes = []): PracticeSession
    {
        return PracticeSession::create($attributes + [
            'child_id' => $child->id, 'exercise_type_id' => $this->typeId($key), 'started_at' => now()->subMinutes(10),
            'planned_duration_seconds' => 600, 'status' => 'completed', 'questions_answered' => 20, 'questions_correct' => 18,
        ]);
    }

    private function earned(Child $child): array
    {
        return $child->badges()->pluck('key')->sort()->values()->all();
    }

    private function evaluate(Child $child, PracticeSession $session): array
    {
        return app(BadgeEvaluator::class)->evaluate($child, $session)->pluck('key')->all();
    }

    private function fastAttempts(PracticeSession $session, string $key, int $count, int $ms): void
    {
        $facts = Fact::where('exercise_type_id', $this->typeId($key))->limit($count)->get();

        foreach ($facts as $fact) {
            SessionAttempt::create([
                'practice_session_id' => $session->id, 'fact_id' => $fact->id, 'given_answer' => $fact->correct_answer, 'is_correct' => true,
                'response_time_ms' => $ms, 'points_awarded' => 10, 'question_issued_at' => now(), 'answered_at' => now(),
            ]);
        }
    }

    private function practiceGroup(Child $child, int $group, int $factsSeen, int $attemptsEach, int $correctEach): void
    {
        $facts = Fact::where('exercise_type_id', $this->typeId('addition'))->where('difficulty_group', $group)->limit($factsSeen)->get();

        foreach ($facts as $fact) {
            ChildFactStat::create(['child_id' => $child->id, 'fact_id' => $fact->id, 'attempts_total' => $attemptsEach, 'attempts_correct' => $correctEach]);
        }
    }

    public function test_the_plus_catalogue_and_the_einmaleins_only_speed_badge(): void
    {
        $keys = Badge::pluck('key')->all();

        foreach (['addition_first_session', 'addition_mastery_1', 'addition_mastery_2', 'addition_mastery_3', 'addition_blitz', 'allrounder'] as $key) {
            $this->assertContains($key, $keys);
        }

        // The old speed badge is now Einmaleins only, so Plus cannot trigger it too easily.
        $this->assertSame('multiplication', Badge::where('key', 'blitz')->first()->exerciseKey());
        $this->assertSame('addition', Badge::where('key', 'addition_blitz')->first()->exerciseKey());
        $this->assertNull(Badge::where('key', 'allrounder')->first()->exerciseKey());
    }

    public function test_the_plus_starter_needs_a_plus_session_but_the_general_first_badge_takes_either(): void
    {
        $einmaleins = Child::factory()->create();
        $this->assertSame(['first_session'], $this->evaluate($einmaleins, $this->makeSession($einmaleins, 'multiplication')));

        $plus = Child::factory()->create();
        $this->assertEqualsCanonicalizing(['first_session', 'addition_first_session'], $this->evaluate($plus, $this->makeSession($plus, 'addition')));
    }

    public function test_a_short_plus_session_does_not_earn_the_starter(): void
    {
        $child = Child::factory()->create();

        $this->assertNotContains('addition_first_session', $this->evaluate($child, $this->makeSession($child, 'addition', ['questions_answered' => 3])));
    }

    public function test_a_plus_group_master_needs_enough_attempts_breadth_and_accuracy(): void
    {
        $master = Child::factory()->create();
        $this->practiceGroup($master, 1, 34, 2, 2); // 68 attempts, 34 facts, 100 %
        $this->assertContains('addition_mastery_1', $this->evaluate($master, $this->makeSession($master, 'addition')));

        $narrow = Child::factory()->create();
        $this->practiceGroup($narrow, 1, 20, 4, 4); // enough attempts, too few facts
        $this->assertNotContains('addition_mastery_1', $this->evaluate($narrow, $this->makeSession($narrow, 'addition')));

        $sloppy = Child::factory()->create();
        $this->practiceGroup($sloppy, 1, 45, 2, 1); // 50 %
        $this->assertNotContains('addition_mastery_1', $this->evaluate($sloppy, $this->makeSession($sloppy, 'addition')));

        $few = Child::factory()->create();
        $this->practiceGroup($few, 1, 34, 1, 1); // only 34 attempts
        $this->assertNotContains('addition_mastery_1', $this->evaluate($few, $this->makeSession($few, 'addition')));
    }

    public function test_each_plus_group_has_its_own_badge_and_einmaleins_rows_are_unaffected(): void
    {
        $child = Child::factory()->create();
        $this->practiceGroup($child, 2, 14, 2, 2); // "Plus mit der 10": 28 attempts over 14 facts

        $earned = $this->evaluate($child, $this->makeSession($child, 'addition'));

        $this->assertContains('addition_mastery_2', $earned);
        $this->assertNotContains('addition_mastery_1', $earned);
        $this->assertNotContains('addition_mastery_3', $earned);
        $this->assertEmpty(array_filter($earned, fn ($key) => str_starts_with($key, 'row_mastery_')));
    }

    public function test_the_plus_speed_badge_counts_only_fast_plus_sessions(): void
    {
        $fast = Child::factory()->create();
        $session = $this->makeSession($fast, 'addition');
        $this->fastAttempts($session, 'addition', 10, 1200);
        $earned = $this->evaluate($fast, $session);

        $this->assertContains('addition_blitz', $earned);
        $this->assertNotContains('blitz', $earned, 'Einmaleins speed badge is not earned in Plus');

        $tooSlow = Child::factory()->create();
        $session = $this->makeSession($tooSlow, 'addition');
        $this->fastAttempts($session, 'addition', 10, 1800);
        $this->assertNotContains('addition_blitz', $this->evaluate($tooSlow, $session));

        $einmaleins = Child::factory()->create();
        $session = $this->makeSession($einmaleins, 'multiplication');
        $this->fastAttempts($session, 'multiplication', 10, 1200);
        $earned = $this->evaluate($einmaleins, $session);
        $this->assertContains('blitz', $earned);
        $this->assertNotContains('addition_blitz', $earned);
    }

    public function test_no_plus_speed_badge_when_plus_has_no_speed_bonus(): void
    {
        $child = $this->childWith(plus: true, plusSpeedBonus: false);
        $session = $this->makeSession($child, 'addition');
        $this->fastAttempts($session, 'addition', 10, 500);

        $this->assertNotContains('addition_blitz', $this->evaluate($child, $session));
    }

    public function test_the_allrounder_needs_both_exercises_properly_practised_on_the_same_day(): void
    {
        $child = $this->childWith();

        $this->makeSession($child, 'multiplication', ['started_at' => now()->subHours(3)]);
        $this->assertNotContains('allrounder', $this->evaluate($child, $this->makeSession($child, 'multiplication')), 'the same exercise twice is not enough');

        $second = $this->makeSession($child, 'addition', ['started_at' => now()->subHour()]);
        $this->assertContains('allrounder', $this->evaluate($child, $second));
    }

    public function test_the_allrounder_ignores_yesterday_and_sessions_that_were_too_short(): void
    {
        $yesterday = $this->childWith();
        $this->makeSession($yesterday, 'multiplication', ['started_at' => now()->subDay()]);
        $this->assertNotContains('allrounder', $this->evaluate($yesterday, $this->makeSession($yesterday, 'addition')));

        $short = $this->childWith();
        $this->makeSession($short, 'multiplication');
        $this->assertNotContains('allrounder', $this->evaluate($short, $this->makeSession($short, 'addition', ['questions_answered' => 2])));
    }

    public function test_only_badges_of_switched_on_exercises_are_attainable(): void
    {
        $einmaleinsOnly = $this->childWith(plus: false);
        $keys = $einmaleinsOnly->attainableBadges()->pluck('key')->all();

        $this->assertCount(12, $keys);
        $this->assertNotContains('addition_mastery_1', $keys);
        $this->assertNotContains('allrounder', $keys);

        $both = $this->childWith(plus: true);
        $this->assertCount(18, $both->attainableBadges());
    }

    public function test_the_plus_speed_badge_disappears_when_plus_has_no_speed_bonus_but_the_einmaleins_one_stays(): void
    {
        $keys = $this->childWith(plus: true, plusSpeedBonus: false)->attainableBadges()->pluck('key')->all();

        $this->assertNotContains('addition_blitz', $keys);
        $this->assertContains('blitz', $keys);
        $this->assertCount(17, $keys);
    }

    public function test_an_earned_plus_badge_stays_visible_after_plus_is_switched_off(): void
    {
        $child = $this->childWith(plus: false);
        $child->badges()->attach(Badge::where('key', 'addition_first_session')->first()->id, ['earned_at' => now()]);

        $keys = $child->attainableBadges()->pluck('key')->all();

        $this->assertContains('addition_first_session', $keys);
        $this->assertNotContains('addition_mastery_1', $keys);
    }

    public function test_the_allrounder_needs_two_exercises_to_be_switched_on(): void
    {
        $child = Child::factory()->create();
        $this->setting($child, 'multiplication', enabled: false);
        $this->setting($child, 'addition', enabled: true);

        $this->assertNotContains('allrounder', $child->attainableBadges()->pluck('key')->all());
    }

    public function test_a_child_without_any_settings_counts_as_set_up_with_the_defaults(): void
    {
        $keys = Child::factory()->create()->attainableBadges()->pluck('key')->all();

        $this->assertCount(12, $keys);
        $this->assertContains('row_mastery_3', $keys);
        $this->assertNotContains('addition_mastery_1', $keys);
    }

    public function test_the_badges_are_grouped_into_general_and_one_section_per_exercise(): void
    {
        $sections = app(BadgeSections::class)->group($this->childWith(plus: true)->attainableBadges());

        $this->assertSame(['Allgemein', 'Einmaleins', 'Plus bis 20'], array_column($sections, 'label'));
        $this->assertSame([false, true, true], array_column($sections, 'exercise'));
        $this->assertSame(['first_session', 'streak_7', 'allrounder'], $sections[0]['badges']->pluck('key')->all());
        $this->assertCount(10, $sections[1]['badges']); // blitz + 9 rows
        $this->assertCount(5, $sections[2]['badges']);  // starter + 3 masters + blitz
    }

    public function test_the_medal_marks_plus_groups_with_their_own_short_mark(): void
    {
        $this->assertSame('+10', Badge::where('key', 'addition_mastery_2')->first()->overlay());
        $this->assertSame('20', Badge::where('key', 'addition_mastery_3')->first()->overlay());
        $this->assertSame('3', Badge::where('key', 'row_mastery_3')->first()->overlay());
        $this->assertNull(Badge::where('key', 'blitz')->first()->overlay());
    }

    public function test_the_childs_badge_page_shows_headings_only_with_several_exercises(): void
    {
        $einmaleinsOnly = $this->childWith(plus: false);
        $this->actingAs($einmaleinsOnly, 'child')->get(route('child.achievements'))->assertOk()
            ->assertSee('(0 von 12)')->assertDontSee('Plus-Starter')->assertDontSee('Plus bis 20');

        $both = $this->childWith(plus: true);
        $this->actingAs($both, 'child')->get(route('child.achievements'))->assertOk()
            ->assertSee('(0 von 18)')
            ->assertSee('Plus-Starter')
            ->assertSee('Meister des Zehnerübergangs')
            ->assertSee('Allrounder')
            ->assertSee('Löse den «Zehnerübergang» zu über 90 % richtig.')
            ->assertSee('Übe am selben Tag mit beiden Übungen.')
            ->assertSee('Allgemein')
            ->assertSee('Einmaleins')
            ->assertSee('Plus bis 20');
    }

    public function test_the_parents_see_the_plus_badges_and_the_adapted_count(): void
    {
        $family = Family::factory()->create();
        $user = User::factory()->for($family)->create();
        $child = Child::factory()->for($family)->create();
        $this->setting($child, 'multiplication');
        $this->setting($child, 'addition');
        $child->badges()->attach(Badge::where('key', 'addition_first_session')->first()->id, ['earned_at' => '2026-09-05 10:00:00']);

        $this->actingAs($user)->get(route('parent.children.statistics', $child))->assertOk()
            ->assertSee('1 von 18 verdient')
            ->assertSee('Plus-Starter')
            ->assertSee('05.09.2026')
            ->assertSee('Meister von Plus mit der 10');
    }

    public function test_a_shared_group_mastery_image_is_used_for_all_plus_masters(): void
    {
        $folder = public_path(self::TEST_BADGE_IMAGES);
        mkdir($folder, 0775, true);
        $image = $folder.'/group_mastery.png';
        file_put_contents($image, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='));

        try {
            foreach (['addition_mastery_1', 'addition_mastery_2', 'addition_mastery_3'] as $key) {
                $this->assertStringContainsString(self::TEST_BADGE_IMAGES.'/group_mastery.png', Badge::where('key', $key)->first()->imageUrl());
            }
        } finally {
            @unlink($image);
        }
    }
}
