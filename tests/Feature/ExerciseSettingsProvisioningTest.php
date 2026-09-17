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
}
