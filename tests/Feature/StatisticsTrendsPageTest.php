<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\ChildExerciseSetting;
use App\Models\ExerciseType;
use App\Models\Fact;
use App\Models\Family;
use App\Models\PracticeSession;
use App\Models\SessionAttempt;
use App\Models\User;
use Database\Seeders\ExerciseTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class StatisticsTrendsPageTest extends TestCase
{
    use RefreshDatabase;

    private User $parent;

    private Child $child;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ExerciseTypeSeeder::class);
        $family = Family::factory()->create();
        $this->parent = User::factory()->for($family)->create();
        $this->child = Child::factory()->for($family)->create();
    }

    private function enable(string ...$keys): void
    {
        foreach ($keys as $key) {
            ChildExerciseSetting::create([
                'child_id' => $this->child->id, 'exercise_type_id' => ExerciseType::where('key', $key)->value('id'),
                'enabled' => true, 'active_groups' => [1], 'session_duration_minutes' => 10, 'target_frequency' => 'daily',
            ]);
        }
    }

    private function round(string $key, int $answered, int $correct, int $daysAgo = 0): void
    {
        PracticeSession::create([
            'child_id' => $this->child->id, 'exercise_type_id' => ExerciseType::where('key', $key)->value('id'),
            'started_at' => now()->subDays($daysAgo)->setTime(12, 0), 'planned_duration_seconds' => 600,
            'status' => 'completed', 'questions_answered' => $answered, 'questions_correct' => $correct,
        ]);
    }

    /** A round whose last answer came $minutes minutes after its start, so it has a measurable pace. */
    private function timedRound(string $key, int $answered, int $correct, float $minutes, int $daysAgo = 0): void
    {
        $this->round($key, $answered, $correct, $daysAgo);
        $session = PracticeSession::latest('id')->first();
        $typeId = ExerciseType::where('key', $key)->value('id');
        $fact = Fact::firstOrCreate(
            ['exercise_type_id' => $typeId, 'operand_a' => 2, 'operand_b' => 3],
            ['correct_answer' => 6, 'difficulty_group' => 2],
        );
        $at = $session->started_at->copy()->addSeconds((int) round($minutes * 60));
        SessionAttempt::create([
            'practice_session_id' => $session->id, 'fact_id' => $fact->id, 'given_answer' => 6, 'is_correct' => true,
            'response_time_ms' => 1500, 'question_issued_at' => $at->copy()->subSecond(), 'answered_at' => $at,
        ]);
    }

    private function page(): TestResponse
    {
        return $this->actingAs($this->parent)->get(route('parent.children.statistics', $this->child))->assertOk();
    }

    public function test_the_bars_split_each_day_into_right_and_wrong_relative_to_the_best_day(): void
    {
        $this->enable('multiplication');
        $this->round('multiplication', 20, 15);
        $this->round('multiplication', 10, 10, daysAgo: 1);

        $html = $this->page()->assertSee('Aufgaben im Verlauf')->assertSee('Richtig und falsch pro Tag')->getContent();

        // Best day = 20 tasks: 15 right = 75 %, 5 wrong = 25 %; the day before is 10 right = 50 %.
        $this->assertStringContainsString('data-chart="tasks-multiplication-week"', $html);
        $this->assertStringContainsString('class="w-full bg-green-500" style="height: 75%;" data-part="correct"', $html);
        $this->assertStringContainsString('class="w-full bg-red-400" style="height: 25%;" data-part="wrong"', $html);
        $this->assertStringContainsString('class="w-full bg-green-500" style="height: 50%;" data-part="correct"', $html);
    }

    public function test_with_one_exercise_there_is_no_exercise_choice_and_with_several_there_is(): void
    {
        $this->enable('multiplication');
        $this->page()->assertDontSee('role="tablist"', false);

        $this->enable('addition');
        $this->page()
            ->assertSee('role="tablist"', false)
            ->assertSee('data-chart="tasks-all-month"', false)
            ->assertSee('data-chart="tasks-addition-week"', false);
    }

    public function test_without_any_round_the_chart_says_so(): void
    {
        $this->enable('multiplication');

        $this->page()->assertSee('Noch keine Runden in diesem Zeitraum.');
    }

    public function test_the_card_is_not_shown_when_there_is_no_exercise_data_at_all(): void
    {
        $this->page()->assertDontSee('Aufgaben im Verlauf');
    }

    public function test_the_pace_card_shows_the_average_and_the_change_against_the_previous_week(): void
    {
        $this->enable('multiplication');
        $this->timedRound('multiplication', 20, 20, 4);            // 5,0 per minute today
        $this->timedRound('multiplication', 10, 10, 2, daysAgo: 1); // 5,0 yesterday, so there is a line to draw
        $this->timedRound('multiplication', 10, 10, 5, daysAgo: 8); // 2,0 per minute last week

        $html = $this->page()->assertSee('Tempo: richtige Aufgaben pro Minute')->getContent();

        $this->assertStringContainsString('data-chart="tempo-multiplication-week"', $html);
        $this->assertStringContainsString('Ø 5,0 richtige pro Minute', $html);
        $this->assertStringContainsString('(+3,0 gegenüber der Vorwoche)', $html);
        $this->assertStringContainsString('<polyline', $html);
    }

    public function test_a_slower_week_is_shown_with_a_minus_and_without_a_previous_period_no_comparison(): void
    {
        $this->enable('multiplication');
        $this->timedRound('multiplication', 10, 10, 5);             // 2,0 per minute today
        $this->timedRound('multiplication', 20, 20, 4, daysAgo: 8); // 5,0 per minute last week

        $this->page()->assertSee('(−3,0 gegenüber der Vorwoche)', false);
    }

    public function test_the_pace_is_not_mixed_across_exercises_but_asks_to_pick_one(): void
    {
        $this->enable('multiplication', 'addition');
        $this->timedRound('multiplication', 20, 20, 4);

        $html = $this->page()->getContent();

        $this->assertStringContainsString('Wähle eine Übung, um das Tempo zu sehen.', $html);
        $this->assertStringContainsString('data-chart="tempo-all-week"', $html);
        $this->assertStringContainsString('data-chart="tempo-multiplication-week"', $html);
    }

    public function test_short_rounds_give_no_pace_and_explain_why(): void
    {
        $this->enable('multiplication');
        $this->timedRound('multiplication', 4, 4, 1);

        $this->page()->assertSee('Eine Runde braucht dafür mindestens 5 Aufgaben.');
    }

    public function test_the_latest_rounds_list_right_wrong_accuracy_and_pace(): void
    {
        $this->enable('multiplication', 'addition');
        $this->timedRound('multiplication', 20, 15, 3);
        $this->round('addition', 4, 4, daysAgo: 1);

        $html = $this->page()->assertSee('Letzte Runden')->getContent();

        $this->assertStringContainsString('data-chart="rounds-all"', $html);
        $this->assertStringContainsString('data-chart="rounds-multiplication"', $html);
        // 15 right in 3 minutes = 5,0 per minute; 15 of 20 = 75 %.
        $this->assertMatchesRegularExpression('/75 %.*?5,0<span class="text-gray-400"> \/ Min\./s', $html);
        // The short addition round has no pace and is not in the multiplication list.
        $this->assertMatchesRegularExpression('/data-chart="rounds-addition".*?100 %.*?–/s', $html);
        $this->assertDoesNotMatchRegularExpression('/data-chart="rounds-multiplication".*?Plus bis 20.*?data-chart="rounds-addition"/s', $html);
    }

    public function test_without_rounds_the_list_says_so(): void
    {
        $this->enable('multiplication');

        $this->page()->assertSee('Noch keine Runden.');
    }
}
