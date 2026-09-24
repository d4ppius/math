<?php

namespace Tests\Feature;

use App\Models\Badge;
use App\Models\Child;
use App\Models\ChildExerciseSetting;
use App\Models\ExerciseType;
use App\Models\Family;
use App\Models\User;
use Database\Seeders\BadgeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChildAchievementsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(BadgeSeeder::class);
    }

    private function earn(Child $child, string $key, string $when = 'now'): void
    {
        $child->badges()->attach(Badge::where('key', $key)->first()->id, ['earned_at' => $when]);
    }

    public function test_it_requires_a_logged_in_child(): void
    {
        $this->get(route('child.achievements'))->assertRedirect();
    }

    public function test_it_shows_level_points_and_the_earned_badges_with_their_date(): void
    {
        $child = Child::factory()->create(['total_points' => 800]);
        ChildExerciseSetting::create([
            'child_id' => $child->id,
            'exercise_type_id' => ExerciseType::create(['key' => 'multiplication', 'name' => 'Einmaleins'])->id,
            'active_groups' => [1], 'session_duration_minutes' => 10, 'target_frequency' => 'daily',
            'points' => 800,
        ]);
        $this->earn($child, 'first_session', '2026-09-01 10:00:00');

        $this->actingAs($child, 'child')
            ->get(route('child.achievements'))
            ->assertOk()
            ->assertSee('Meine Erfolge')
            ->assertSee('800 Punkte')
            ->assertSee('Level 2')
            ->assertSee('Deine Abzeichen')
            ->assertSee('(1 von 12)')
            ->assertSee('Erste Übung')
            ->assertSee('01.09.2026');
    }

    public function test_with_a_single_exercise_the_level_has_no_heading(): void
    {
        $child = Child::factory()->create();
        ChildExerciseSetting::create([
            'child_id' => $child->id,
            'exercise_type_id' => ExerciseType::create(['key' => 'multiplication', 'name' => 'Einmaleins'])->id,
            'active_groups' => [1], 'session_duration_minutes' => 10, 'target_frequency' => 'daily',
            'points' => 1000,
        ]);

        $this->actingAs($child, 'child')->get(route('child.achievements'))
            ->assertSee('Level 2')
            ->assertDontSee('<h3 class="mb-1 text-sm font-semibold uppercase tracking-wide text-gray-400">Einmaleins</h3>', false);
    }

    public function test_with_several_exercises_each_level_gets_its_own_heading(): void
    {
        $child = Child::factory()->create();
        ChildExerciseSetting::create([
            'child_id' => $child->id,
            'exercise_type_id' => ExerciseType::create(['key' => 'multiplication', 'name' => 'Einmaleins'])->id,
            'active_groups' => [1], 'session_duration_minutes' => 10, 'target_frequency' => 'daily',
            'points' => 1000,
        ]);
        ChildExerciseSetting::create([
            'child_id' => $child->id,
            'exercise_type_id' => ExerciseType::create(['key' => 'addition', 'name' => 'Plus bis 20'])->id,
            'active_groups' => [1], 'session_duration_minutes' => 10, 'target_frequency' => 'daily',
            'points' => 5000,
        ]);

        $this->actingAs($child, 'child')->get(route('child.achievements'))
            ->assertSeeInOrder(['Einmaleins', 'Level 2', 'Plus bis 20', 'Level 4']);
    }

    public function test_an_exercise_switched_off_still_shows_its_level_if_it_has_points(): void
    {
        $child = Child::factory()->create();
        ChildExerciseSetting::create([
            'child_id' => $child->id,
            'exercise_type_id' => ExerciseType::create(['key' => 'multiplication', 'name' => 'Einmaleins'])->id,
            'active_groups' => [1], 'session_duration_minutes' => 10, 'target_frequency' => 'daily',
            'points' => 1000,
        ]);
        ChildExerciseSetting::create([
            'child_id' => $child->id, 'enabled' => false,
            'exercise_type_id' => ExerciseType::create(['key' => 'addition', 'name' => 'Plus bis 20'])->id,
            'active_groups' => [1], 'session_duration_minutes' => 10, 'target_frequency' => 'daily',
            'points' => 5000,
        ]);

        $this->actingAs($child, 'child')->get(route('child.achievements'))
            ->assertSee('Plus bis 20')
            ->assertSee('Level 4');
    }

    public function test_locked_badges_are_shown_greyed_with_how_to_get_them(): void
    {
        $child = Child::factory()->create();
        $this->earn($child, 'first_session');

        $this->actingAs($child, 'child')
            ->get(route('child.achievements'))
            ->assertSee('Das kannst du noch schaffen')
            ->assertSee('Meister der 4er-Reihe')
            ->assertSee('Löse die 4er-Reihe zu über 90 % richtig.')
            ->assertSee('Erreiche 7 Tage hintereinander dein Tagesziel.')
            ->assertSee('filter:grayscale(1)', false);
    }

    public function test_with_the_setting_off_only_earned_badges_are_shown(): void
    {
        $child = Child::factory()->create(['show_locked_badges' => false]);
        $this->earn($child, 'first_session');

        $this->actingAs($child, 'child')
            ->get(route('child.achievements'))
            ->assertOk()
            ->assertSee('Erste Übung')
            ->assertSee('(1 von 1)')
            ->assertDontSee('Das kannst du noch schaffen')
            ->assertDontSee('Meister der 4er-Reihe')
            ->assertDontSee('filter:grayscale(1)', false);
    }

    public function test_the_speed_badge_is_not_shown_as_a_goal_when_the_speed_bonus_is_off(): void
    {
        $child = Child::factory()->create();
        ChildExerciseSetting::create([
            'child_id' => $child->id,
            'exercise_type_id' => ExerciseType::create(['key' => 'multiplication', 'name' => 'Einmaleins'])->id,
            'active_groups' => [1], 'session_duration_minutes' => 10, 'target_frequency' => 'daily',
            'speed_bonus_enabled' => false,
        ]);

        $this->actingAs($child, 'child')
            ->get(route('child.achievements'))
            ->assertDontSee('Blitzrechner')
            ->assertSee('(0 von 11)');

        // An already earned speed badge stays visible.
        $this->earn($child, 'blitz');

        $this->actingAs($child->fresh(), 'child')->get(route('child.achievements'))->assertSee('Blitzrechner');
    }

    public function test_a_child_with_every_badge_gets_a_congratulation(): void
    {
        $child = Child::factory()->create();
        foreach (Badge::pluck('key') as $key) {
            $this->earn($child, $key);
        }

        $this->actingAs($child, 'child')
            ->get(route('child.achievements'))
            ->assertSee('Wow, du hast alle Abzeichen!')
            ->assertDontSee('Das kannst du noch schaffen');
    }

    public function test_the_medal_keeps_its_size_when_the_caller_adds_a_style(): void
    {
        $child = Child::factory()->create();
        $this->earn($child, 'first_session');

        // The home card overlaps its small medals via an extra style.
        $html = $this->actingAs($child, 'child')->get(route('child.home'))->getContent();

        $this->assertStringContainsString('width:36px;height:36px;margin-left:-6px', $html);
        $this->assertSame(1, preg_match_all('/title="Erste Übung"[^>]*style="/', $html), 'exactly one style attribute');
    }

    public function test_parents_can_toggle_whether_locked_badges_are_shown(): void
    {
        $family = Family::factory()->create();
        $user = User::factory()->for($family)->create();
        $child = Child::factory()->for($family)->create();

        $this->assertTrue($child->refresh()->show_locked_badges, 'On by default.');

        $payload = fn (int $show) => [
            'name' => $child->name, 'avatar' => 'fox', 'color_theme' => 'blue', 'active' => 1, 'show_locked_badges' => $show,
        ];

        $this->actingAs($user)->put(route('parent.children.update', $child), $payload(0))->assertRedirect();
        $this->assertFalse($child->refresh()->show_locked_badges);

        $this->actingAs($user)->put(route('parent.children.update', $child), $payload(1))->assertRedirect();
        $this->assertTrue($child->refresh()->show_locked_badges);

        $this->actingAs($user)->get(route('parent.children.edit', $child))
            ->assertOk()
            ->assertSee('Abzeichen zeigen, die noch nicht verdient sind');
    }

    public function test_a_new_child_shows_locked_badges_by_default(): void
    {
        $user = User::factory()->for(Family::factory()->create())->create();

        $this->actingAs($user)->post(route('parent.children.store'), [
            'name' => 'Neu', 'avatar' => 'owl', 'color_theme' => 'green',
        ])->assertRedirect();

        $this->assertTrue(Child::where('name', 'Neu')->firstOrFail()->show_locked_badges);
    }
}
