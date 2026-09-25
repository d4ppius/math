<?php

namespace Tests\Feature;

use App\Http\Controllers\ChildPreviewController;
use App\Models\Child;
use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChildPreviewTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->is_admin = true;
        $admin->save();

        return $admin;
    }

    public function test_a_parent_can_preview_their_own_child_and_return(): void
    {
        $family = Family::factory()->create();
        $parent = User::factory()->for($family)->create();
        $child = Child::factory()->for($family)->create(['name' => 'Liv']);

        $this->actingAs($parent)
            ->from(route('parent.children.edit', $child))
            ->post(route('child-preview.start', $child))
            ->assertRedirect(route('child.home'));

        $this->assertAuthenticatedAs($child, 'child');

        $this->get(route('child.home'))
            ->assertOk()
            ->assertSee('Vorschau als Liv', false)
            ->assertSee('Vorschau beenden');

        $this->post(route('child-preview.stop'))
            ->assertRedirect(route('parent.children.edit', $child));

        // Explicit guard: the "stop" request's own auth:child middleware just
        // switched Auth's default guard to "child" as a side effect of
        // authenticating that request, same as any guard-specific middleware
        // — only a testing-harness artifact (a real request always boots
        // fresh), so the next call restates who a real browser would be.
        $this->assertAuthenticatedAs($parent, 'web');
        $this->actingAs($parent)->get(route('parent.children.edit', $child))->assertOk()->assertDontSee('Vorschau beenden');
    }

    public function test_a_parent_cannot_preview_another_familys_child(): void
    {
        $parent = User::factory()->for(Family::factory())->create();
        $otherChild = Child::factory()->for(Family::factory())->create();

        $this->actingAs($parent)
            ->post(route('child-preview.start', $otherChild))
            ->assertForbidden();

        $this->assertGuest('child');
    }

    public function test_an_admin_can_preview_any_child(): void
    {
        $child = Child::factory()->for(Family::factory())->create();

        $this->actingAs($this->admin())
            ->post(route('child-preview.start', $child))
            ->assertRedirect(route('child.home'));

        $this->assertAuthenticatedAs($child, 'child');
    }

    public function test_a_guest_cannot_start_a_preview(): void
    {
        $child = Child::factory()->create();

        $this->post(route('child-preview.start', $child))->assertRedirect();
    }

    public function test_a_child_cannot_start_a_preview(): void
    {
        // "start" needs the parent/admin (web) guard, not the child guard.
        $child = Child::factory()->create();

        $this->actingAs($child, 'child')->post(route('child-preview.start', $child))->assertRedirect();
    }

    public function test_a_guest_cannot_stop_a_preview(): void
    {
        $this->post(route('child-preview.stop'))->assertRedirect();
    }

    public function test_stopping_without_a_real_preview_running_is_forbidden(): void
    {
        $child = Child::factory()->create();

        // No previewer_id in the session at all.
        $this->actingAs($child, 'child')->post(route('child-preview.stop'))->assertForbidden();

        // A forged session value pointing at a non-existent user is rejected too.
        $this->actingAs($child, 'child')
            ->withSession([ChildPreviewController::SESSION_KEY => 999999])
            ->post(route('child-preview.stop'))
            ->assertForbidden();
    }

    public function test_starting_a_preview_while_already_previewing_is_blocked(): void
    {
        $family = Family::factory()->create();
        $parent = User::factory()->for($family)->create();
        $childOne = Child::factory()->for($family)->create();
        $childTwo = Child::factory()->for($family)->create();

        $this->actingAs($parent)->post(route('child-preview.start', $childOne))->assertRedirect(route('child.home'));

        // The web guard is still the parent (only the separate child guard
        // changed), so this needs no fresh actingAs() — exactly the scenario
        // of a stray second tab trying to open another preview.
        $this->from(route('child.home'))
            ->post(route('child-preview.start', $childTwo))
            ->assertRedirect(route('child.home'))
            ->assertSessionHas('error');
    }

    public function test_starting_a_preview_while_impersonating_is_blocked(): void
    {
        $admin = $this->admin();
        $parent = User::factory()->for(Family::factory())->create();
        $child = Child::factory()->for($parent->family)->create();

        $this->actingAs($admin)->post(route('admin.users.impersonate', $parent))->assertRedirect(route('dashboard'));

        $this->from(route('dashboard'))
            ->post(route('child-preview.start', $child))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error');
    }

    public function test_the_preview_banner_only_appears_during_a_preview(): void
    {
        $child = Child::factory()->create();

        $this->actingAs($child, 'child')->get(route('child.home'))->assertOk()->assertDontSee('Vorschau beenden');
    }
}
