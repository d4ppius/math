<?php

namespace Tests\Feature;

use App\Models\Badge;
use App\Models\Child;
use App\Models\ExerciseType;
use App\Models\PracticeSession;
use App\Services\Gamification\BadgeEvaluator;
use App\Services\Gamification\BadgeSections;
use Database\Seeders\BadgeSeeder;
use Database\Seeders\ExerciseTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeekdayBadgesTest extends TestCase
{
    use RefreshDatabase;

    private const MONDAY = '2026-09-28 16:00:00';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ExerciseTypeSeeder::class);
        $this->seed(BadgeSeeder::class);
    }

    private function evaluate(Child $child, string $startedAt, int $questions = 20): array
    {
        $session = PracticeSession::create([
            'child_id' => $child->id, 'exercise_type_id' => ExerciseType::where('key', 'multiplication')->value('id'),
            'started_at' => $startedAt, 'planned_duration_seconds' => 600, 'status' => 'completed',
            'questions_answered' => $questions, 'questions_correct' => $questions,
        ]);

        return app(BadgeEvaluator::class)->evaluate($child, $session)
            ->pluck('key')->filter(fn ($key) => str_starts_with($key, 'weekday_'))->values()->all();
    }

    public function test_the_catalogue_has_one_animal_per_weekday(): void
    {
        $animals = Badge::where('key', 'like', 'weekday_%')->orderBy('key')->pluck('name', 'key')->all();

        $this->assertSame([
            'weekday_1' => 'Igel', 'weekday_2' => 'Hund', 'weekday_3' => 'Pferd', 'weekday_4' => 'Katze',
            'weekday_5' => 'Hase', 'weekday_6' => 'Elefant', 'weekday_7' => 'Faultier',
        ], $animals);
        $this->assertStringContainsString('Montag', Badge::where('key', 'weekday_1')->first()->description);
    }

    public function test_a_monday_session_earns_only_the_monday_friend(): void
    {
        $child = Child::factory()->create();

        $this->assertSame(['weekday_1'], $this->evaluate($child, self::MONDAY));
    }

    public function test_every_weekday_earns_its_own_friend(): void
    {
        foreach (range(1, 7) as $day) {
            $child = Child::factory()->create();
            $date = now()->setDate(2026, 9, 27 + $day)->setTime(16, 0)->toDateTimeString(); // 28.9. is a Monday

            $this->assertSame(["weekday_{$day}"], $this->evaluate($child, $date));
        }
    }

    public function test_a_session_that_is_too_short_earns_nothing(): void
    {
        $child = Child::factory()->create();

        $this->assertSame([], $this->evaluate($child, self::MONDAY, questions: 4));
    }

    public function test_a_friend_is_only_earned_once_and_a_second_day_adds_another(): void
    {
        $child = Child::factory()->create();

        $this->assertSame(['weekday_1'], $this->evaluate($child, self::MONDAY));
        $this->assertSame([], $this->evaluate($child, '2026-10-05 16:00:00')); // the next Monday
        $this->assertSame(['weekday_2'], $this->evaluate($child, '2026-09-29 16:00:00'));
    }

    public function test_the_weekday_friends_are_attainable_for_every_child(): void
    {
        $keys = Child::factory()->create()->attainableBadges()->pluck('key')->all();

        foreach (range(1, 7) as $day) {
            $this->assertContains("weekday_{$day}", $keys);
        }
    }

    public function test_the_badge_page_groups_the_general_badges_under_headings(): void
    {
        $child = Child::factory()->create();

        $sections = app(BadgeSections::class)->group($child->attainableBadges());
        $this->assertSame(['Meilensteine', 'Level', 'Freunde vom Fuchs', 'Einmaleins'], array_column($sections, 'label'));

        $this->actingAs($child, 'child')->get(route('child.achievements'))->assertOk()
            ->assertSee('Meilensteine')
            ->assertSee('Freunde vom Fuchs')
            ->assertSee('Igel')
            ->assertSee('Übe an einem Montag mindestens 5 Aufgaben.')
            ->assertSee('🦥');
    }
}
