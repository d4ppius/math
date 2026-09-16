<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParentChildTenancyTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_parent_cannot_edit_another_familys_child(): void
    {
        $familyA = Family::factory()->create();
        $familyB = Family::factory()->create();
        $childOfFamilyA = Child::factory()->for($familyA)->create();
        $userOfFamilyB = User::factory()->for($familyB)->create();

        $this->actingAs($userOfFamilyB)
            ->get(route('parent.children.edit', $childOfFamilyA))
            ->assertForbidden();
    }

    public function test_a_parent_only_sees_their_own_familys_children_on_the_dashboard(): void
    {
        $family = Family::factory()->create();
        $otherFamily = Family::factory()->create();
        $user = User::factory()->for($family)->create();
        Child::factory()->for($family)->create(['name' => 'Lea']);
        Child::factory()->for($otherFamily)->create(['name' => 'Max']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertSee('Lea')
            ->assertDontSee('Max');
    }

    public function test_creating_a_child_shows_the_magic_link_once(): void
    {
        $family = Family::factory()->create();
        $user = User::factory()->for($family)->create();

        $response = $this->actingAs($user)->post(route('parent.children.store'), [
            'name' => 'Lea',
            'avatar' => 'fox',
            'color_theme' => 'orange',
        ]);

        $child = Child::where('name', 'Lea')->first();
        $response->assertRedirect(route('parent.children.edit', $child));

        $plainToken = session('plain_login_token');
        $this->assertNotEmpty($plainToken);

        $this->actingAs($user)
            ->get(route('parent.children.edit', $child))
            ->assertSee(route('child.magic-link', ['token' => $plainToken]));
    }
}
