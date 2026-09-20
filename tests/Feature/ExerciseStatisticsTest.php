<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\ChildExerciseSetting;
use App\Models\ChildFactStat;
use App\Models\ExerciseType;
use App\Models\Fact;
use App\Models\Family;
use App\Models\User;
use Database\Seeders\ExerciseTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExerciseStatisticsTest extends TestCase
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
        $this->child = Child::factory()->for($family)->create(['name' => 'Mia']);
    }

    private function setting(string $key, bool $enabled): void
    {
        ChildExerciseSetting::create([
            'child_id' => $this->child->id, 'exercise_type_id' => ExerciseType::where('key', $key)->value('id'), 'enabled' => $enabled,
            'active_groups' => [1], 'session_duration_minutes' => 10, 'target_frequency' => 'daily',
        ]);
    }

    private function practice(string $key, int $a, int $b, int $attempts, int $correct): void
    {
        $fact = Fact::where('operand_a', $a)->where('operand_b', $b)
            ->where('exercise_type_id', ExerciseType::where('key', $key)->value('id'))->firstOrFail();

        ChildFactStat::create(['child_id' => $this->child->id, 'fact_id' => $fact->id, 'attempts_total' => $attempts, 'attempts_correct' => $correct]);
    }

    private function page()
    {
        return $this->actingAs($this->parent)->get(route('parent.children.statistics', $this->child));
    }

    public function test_with_only_einmaleins_there_is_one_heatmap_without_tabs_and_with_the_times_sign(): void
    {
        $this->setting('multiplication', true);
        $this->practice('multiplication', 7, 8, 10, 9);

        $this->page()->assertOk()
            ->assertSee('Einmaleins')
            ->assertSee('7 × 8 = 56')
            ->assertSee('×10')
            ->assertSee('90%')
            ->assertDontSee('role="tablist"', false);
    }

    public function test_with_plus_switched_on_each_exercise_gets_a_heatmap_and_a_tab(): void
    {
        $this->setting('multiplication', true);
        $this->setting('addition', true);
        $this->practice('addition', 7, 8, 4, 3);

        $this->page()->assertOk()
            ->assertSee('role="tablist"', false)
            ->assertSee('Einmaleins')
            ->assertSee('Plus bis 20')
            ->assertSee('7 + 8 = 15')
            ->assertSee('+10')
            ->assertSee('10+')
            ->assertSee('75%');
    }

    public function test_plus_that_is_off_and_never_practised_is_not_shown(): void
    {
        $this->setting('multiplication', true);
        $this->setting('addition', false);

        $this->page()->assertOk()->assertDontSee('Plus bis 20')->assertDontSee('7 + 8 = 15')->assertDontSee('role="tablist"', false);
    }

    public function test_a_switched_off_exercise_keeps_its_history_visible_with_a_note(): void
    {
        $this->setting('multiplication', true);
        $this->setting('addition', false);
        $this->practice('addition', 3, 4, 5, 5);

        $this->page()->assertOk()
            ->assertSee('Plus bis 20')
            ->assertSee('3 + 4 = 7')
            ->assertSee('für Mia ausgeschaltet')
            ->assertSee('(aus)');
    }

    public function test_a_child_without_any_exercise_setting_gets_the_empty_note(): void
    {
        $this->page()->assertOk()->assertSee('Noch keine Übungsdaten vorhanden.');
    }
}
