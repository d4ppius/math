<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\ExerciseType;
use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExerciseSettingsProvisioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_settings_page_self_heals_a_child_that_has_no_settings_yet(): void
    {
        // Reproduces the real-world bug: a child created while exercise
        // types weren't seeded yet ends up with zero child_exercise_settings
        // rows, and the settings page used to render completely empty.
        $exerciseType = ExerciseType::create(['key' => 'multiplication', 'name' => 'Einmaleins']);

        $family = Family::factory()->create();
        $user = User::factory()->for($family)->create();
        $child = Child::factory()->for($family)->create();

        $this->assertSame(0, $child->exerciseSettings()->count());

        $response = $this->actingAs($user)->get(route('parent.children.exercise-settings.edit', $child));

        $response->assertOk()->assertSee($exerciseType->name);
        $this->assertSame(1, $child->exerciseSettings()->count());
    }

    public function test_it_does_not_duplicate_settings_on_repeated_visits(): void
    {
        ExerciseType::create(['key' => 'multiplication', 'name' => 'Einmaleins']);

        $family = Family::factory()->create();
        $user = User::factory()->for($family)->create();
        $child = Child::factory()->for($family)->create();

        $this->actingAs($user)->get(route('parent.children.exercise-settings.edit', $child));
        $this->actingAs($user)->get(route('parent.children.exercise-settings.edit', $child));

        $this->assertSame(1, $child->exerciseSettings()->count());
    }

    public function test_parents_can_toggle_the_timer_visibility_per_exercise(): void
    {
        ExerciseType::create(['key' => 'multiplication', 'name' => 'Einmaleins']);

        $family = Family::factory()->create();
        $user = User::factory()->for($family)->create();
        $child = Child::factory()->for($family)->create();

        $this->actingAs($user)->get(route('parent.children.exercise-settings.edit', $child));
        $setting = $child->exerciseSettings()->first();

        $this->assertTrue($setting->show_timer, 'The timer is shown by default.');

        $payload = fn (int $showTimer) => ['settings' => [[
            'id' => $setting->id,
            'active_groups' => [1],
            'session_duration_minutes' => 10,
            'target_frequency' => 'daily',
            'show_timer' => $showTimer,
            'speed_bonus_enabled' => 1,
        ]]];

        $this->actingAs($user)
            ->put(route('parent.children.exercise-settings.update', $child), $payload(0))
            ->assertRedirect();
        $this->assertFalse($setting->refresh()->show_timer);

        $this->actingAs($user)
            ->put(route('parent.children.exercise-settings.update', $child), $payload(1))
            ->assertRedirect();
        $this->assertTrue($setting->refresh()->show_timer);
    }

    public function test_parents_can_switch_the_speed_bonus_off_per_exercise(): void
    {
        ExerciseType::create(['key' => 'multiplication', 'name' => 'Einmaleins']);

        $family = Family::factory()->create();
        $user = User::factory()->for($family)->create();
        $child = Child::factory()->for($family)->create();

        $this->actingAs($user)->get(route('parent.children.exercise-settings.edit', $child));
        $setting = $child->exerciseSettings()->first();

        $this->assertTrue($setting->speed_bonus_enabled, 'The speed bonus is on by default.');

        $this->actingAs($user)
            ->put(route('parent.children.exercise-settings.update', $child), ['settings' => [[
                'id' => $setting->id,
                'active_groups' => [1],
                'session_duration_minutes' => 10,
                'target_frequency' => 'daily',
                'show_timer' => 1,
                'speed_bonus_enabled' => 0,
            ]]])
            ->assertRedirect();

        $this->assertFalse($setting->refresh()->speed_bonus_enabled);
    }
}
