<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\ChildExerciseSetting;
use App\Models\ExerciseType;
use App\Models\Family;
use App\Models\User;
use Database\Seeders\ExerciseTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChildLevelTest extends TestCase
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

    private function setting(Child $child, string $key, int $points, bool $enabled = true): ChildExerciseSetting
    {
        return ChildExerciseSetting::create([
            'child_id' => $child->id, 'exercise_type_id' => $this->typeId($key), 'enabled' => $enabled,
            'active_groups' => [1], 'session_duration_minutes' => 10, 'target_frequency' => 'daily', 'points' => $points,
        ]);
    }

    public function test_with_a_single_exercise_the_child_home_shows_its_level_title_and_progress(): void
    {
        $child = Child::factory()->create();
        $this->setting($child, 'multiplication', 1000);

        $this->actingAs($child, 'child')
            ->get(route('child.home'))
            ->assertOk()
            ->assertSee('Level 2')
            ->assertSee('Zahlen-Entdecker')
            ->assertSee('Noch 500 Punkte bis Level 3')
            ->assertSee('width: 50%', false);
    }

    public function test_the_top_level_shows_a_congratulation_instead_of_a_next_level(): void
    {
        $child = Child::factory()->create();
        $this->setting($child, 'multiplication', 60000);

        $this->actingAs($child, 'child')
            ->get(route('child.home'))
            ->assertSee('Level 10')
            ->assertSee('Höchstes Level erreicht!')
            ->assertDontSee('Punkte bis Level');
    }

    public function test_with_several_exercises_each_card_shows_its_own_level_and_progress_bar(): void
    {
        $child = Child::factory()->create();
        $this->setting($child, 'multiplication', 1000); // Level 2
        $this->setting($child, 'addition', 5000); // Level 4

        $html = $this->actingAs($child, 'child')->get(route('child.home'))->assertOk()->getContent();

        $this->assertStringContainsString('Level 2 · Zahlen-Entdecker', $html);
        $this->assertStringContainsString('Level 4 · Zahlen-Flitzer', $html);
        // One progress bar per card, no single shared bar above the choice.
        $this->assertSame(2, substr_count($html, 'role="progressbar"'));
    }

    public function test_parents_see_the_level_on_the_statistics_page(): void
    {
        $family = Family::factory()->create();
        $user = User::factory()->for($family)->create();
        $child = Child::factory()->for($family)->create(['total_points' => 2000]);
        $this->setting($child, 'multiplication', 2000);

        $this->actingAs($user)
            ->get(route('parent.children.statistics', $child))
            ->assertOk()
            ->assertSee('Level 3')
            ->assertSee('Rechen-Lehrling')
            ->assertSee('2000 Punkte insgesamt');
    }
}
