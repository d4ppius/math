<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\ChildExerciseSetting;
use App\Models\ExerciseType;
use App\Models\Family;
use App\Models\PracticeSession;
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
}
