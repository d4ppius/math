<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReminderSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function familyWithChild(): array
    {
        $family = Family::factory()->create();
        $user = User::factory()->for($family)->create();
        $child = Child::factory()->for($family)->create();

        return [$user, $child];
    }

    private function payload(Child $child, array $overrides = []): array
    {
        return $overrides + [
            'name' => $child->name, 'avatar' => 'fox', 'color_theme' => 'orange', 'active' => 1,
        ];
    }

    public function test_a_new_child_is_reminded_by_default_at_the_old_fixed_time(): void
    {
        [, $child] = $this->familyWithChild();

        $this->assertTrue($child->reminders_enabled);
        $this->assertSame('17:00:00', $child->reminder_time);
    }

    public function test_parents_can_switch_reminders_off(): void
    {
        [$user, $child] = $this->familyWithChild();

        $this->actingAs($user)
            ->put(route('parent.children.update', $child), $this->payload($child, ['reminders_enabled' => 0]))
            ->assertRedirect(route('parent.children.edit', $child));

        $this->assertFalse($child->refresh()->reminders_enabled);
        // Switching off doesn't touch the time, so turning it back on later keeps it.
        $this->assertSame('17:00:00', $child->reminder_time);
    }

    public function test_parents_can_change_the_reminder_time(): void
    {
        [$user, $child] = $this->familyWithChild();

        $this->actingAs($user)
            ->put(route('parent.children.update', $child), $this->payload($child, ['reminders_enabled' => 1, 'reminder_time' => '08:15']))
            ->assertRedirect(route('parent.children.edit', $child));

        $this->assertTrue($child->refresh()->reminders_enabled);
        $this->assertSame('08:15:00', $child->reminder_time);
    }

    public function test_the_edit_page_shows_the_current_reminder_settings(): void
    {
        [$user, $child] = $this->familyWithChild();
        $child->update(['reminder_time' => '19:30:00']);

        $this->actingAs($user)
            ->get(route('parent.children.edit', $child))
            ->assertOk()
            ->assertSee('An die Übung erinnern')
            ->assertSee('value="19:30"', false);
    }
}
