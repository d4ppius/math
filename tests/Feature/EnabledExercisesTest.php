<?php

namespace Tests\Feature;

use App\Console\Commands\SendPracticeReminders;
use App\Models\Child;
use App\Models\ChildExerciseSetting;
use App\Models\ExerciseType;
use App\Models\Family;
use App\Models\User;
use Database\Seeders\ExerciseTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class EnabledExercisesTest extends TestCase
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

    private function settingFor(string $key): ChildExerciseSetting
    {
        return $this->child->exerciseSettings()
            ->where('exercise_type_id', ExerciseType::where('key', $key)->value('id'))
            ->firstOrFail();
    }

    /** The settings form as a parent would submit it. */
    private function submit(array $byKey): TestResponse
    {
        $settings = [];

        foreach ($byKey as $key => $overrides) {
            $settings[] = $overrides + [
                'id' => $this->settingFor($key)->id,
                'enabled' => 1,
                'active_groups' => [1],
                'session_duration_minutes' => 10,
                'target_frequency' => 'daily',
                'show_timer' => 1,
                'speed_bonus_enabled' => 1,
                'sound_enabled' => 1,
            ];
        }

        return $this->actingAs($this->parent)->put(route('parent.children.exercise-settings.update', $this->child), ['settings' => $settings]);
    }

    public function test_a_new_exercise_is_created_switched_off_and_einmaleins_stays_on(): void
    {
        $this->actingAs($this->parent)->get(route('parent.children.exercise-settings.edit', $this->child))->assertOk();

        $this->assertSame(2, $this->child->exerciseSettings()->count());
        $this->assertTrue($this->settingFor('multiplication')->enabled);
        $this->assertFalse($this->settingFor('addition')->enabled);
        $this->assertSame([1], $this->settingFor('addition')->active_groups);
    }

    public function test_the_settings_page_shows_a_card_per_exercise_with_a_switch_and_its_own_group_names(): void
    {
        $this->actingAs($this->parent)->get(route('parent.children.exercise-settings.edit', $this->child))
            ->assertOk()
            ->assertSee('Einmaleins')
            ->assertSee('Plus bis 20')
            ->assertSee('Für Mia freischalten')
            ->assertSee('Aktive Reihen')
            ->assertSee('Aktive Aufgabenarten')
            ->assertSee('Plus bis 10')
            ->assertSee('Plus mit der 10')
            ->assertSee('Zehnerübergang');
    }

    public function test_parents_can_switch_plus_on_with_its_groups(): void
    {
        $this->actingAs($this->parent)->get(route('parent.children.exercise-settings.edit', $this->child));

        $this->submit(['multiplication' => [], 'addition' => ['active_groups' => [1, 3]]])
            ->assertRedirect(route('parent.children.exercise-settings.edit', $this->child))
            ->assertSessionHasNoErrors();

        $plus = $this->settingFor('addition');
        $this->assertTrue($plus->enabled);
        $this->assertSame([1, 3], $plus->active_groups);
    }

    public function test_einmaleins_can_be_switched_off_while_plus_is_on_and_its_settings_are_kept(): void
    {
        $this->actingAs($this->parent)->get(route('parent.children.exercise-settings.edit', $this->child));

        $this->submit(['multiplication' => ['enabled' => 0, 'active_groups' => [1, 2, 5]], 'addition' => []])->assertSessionHasNoErrors();

        $this->assertFalse($this->settingFor('multiplication')->enabled);
        $this->assertSame([1, 2, 5], $this->settingFor('multiplication')->active_groups);
    }

    public function test_at_least_one_exercise_has_to_stay_on(): void
    {
        $this->actingAs($this->parent)->get(route('parent.children.exercise-settings.edit', $this->child));

        $this->submit(['multiplication' => ['enabled' => 0], 'addition' => ['enabled' => 0]])->assertSessionHasErrors('settings');

        $this->assertTrue($this->settingFor('multiplication')->enabled);
    }

    public function test_an_enabled_exercise_needs_at_least_one_group(): void
    {
        $this->actingAs($this->parent)->get(route('parent.children.exercise-settings.edit', $this->child));

        $this->submit(['multiplication' => [], 'addition' => ['active_groups' => []]])->assertSessionHasErrors('settings.1.active_groups');

        $this->assertFalse($this->settingFor('addition')->enabled);
    }

    public function test_only_groups_the_exercise_offers_are_accepted(): void
    {
        $this->actingAs($this->parent)->get(route('parent.children.exercise-settings.edit', $this->child));

        // Plus has groups 1 to 3; 7 is an Einmaleins row.
        $this->submit(['multiplication' => [], 'addition' => ['active_groups' => [1, 7]]])->assertSessionHasErrors('settings.1.active_groups');

        $this->assertSame([1], $this->settingFor('addition')->active_groups);
    }

    public function test_the_page_lists_the_reasons_when_saving_is_refused(): void
    {
        $this->actingAs($this->parent)->get(route('parent.children.exercise-settings.edit', $this->child));

        $this->from(route('parent.children.exercise-settings.edit', $this->child))
            ->followingRedirects()
            ->submit(['multiplication' => ['enabled' => 0], 'addition' => ['enabled' => 0]])
            ->assertSee('Mindestens eine Übung muss freigeschaltet bleiben');
    }

    public function test_another_family_cannot_change_the_settings(): void
    {
        $this->actingAs($this->parent)->get(route('parent.children.exercise-settings.edit', $this->child));
        $stranger = User::factory()->create();

        $this->actingAs($stranger)->put(route('parent.children.exercise-settings.update', $this->child), ['settings' => []])->assertForbidden();
    }

    public function test_reminders_ignore_exercises_that_are_switched_off(): void
    {
        $this->actingAs($this->parent)->get(route('parent.children.exercise-settings.edit', $this->child));

        // Einmaleins only on weekdays; Plus (off) says daily. Plus must not make weekends count.
        $this->settingFor('multiplication')->update(['target_frequency' => 'weekdays']);
        $this->settingFor('addition')->update(['target_frequency' => 'daily', 'enabled' => false]);

        $command = new SendPracticeReminders;
        $isPracticeDay = new \ReflectionMethod($command, 'isPracticeDayFor');

        $child = Child::with(['exerciseSettings' => fn ($query) => $query->where('enabled', true)])->find($this->child->id);

        $this->assertTrue($isPracticeDay->invoke($command, $child, 3), 'Wednesday');
        $this->assertFalse($isPracticeDay->invoke($command, $child, 6), 'Saturday');
    }
}
