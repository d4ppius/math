<?php

namespace Tests\Feature;

use App\Http\Controllers\Parent\ChildController;
use App\Models\Child;
use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChildAvatarTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_create_page_offers_all_sixteen_avatars(): void
    {
        $user = User::factory()->for(Family::factory())->create();

        $this->assertCount(16, ChildController::AVATARS);

        $response = $this->actingAs($user)->get(route('parent.children.create'))->assertOk();

        foreach (ChildController::AVATARS as $avatar) {
            $response->assertSee('value="'.$avatar.'"', false);
        }
    }

    public function test_a_child_can_be_created_with_one_of_the_new_avatars(): void
    {
        $user = User::factory()->for(Family::factory())->create();

        $this->actingAs($user)->post(route('parent.children.store'), [
            'name' => 'Finn', 'avatar' => 'wolf', 'color_theme' => 'blue',
        ])->assertRedirect();

        $this->assertSame('wolf', Child::where('name', 'Finn')->firstOrFail()->avatar);
    }

    public function test_the_childs_own_home_screen_shows_a_new_avatar(): void
    {
        $child = Child::factory()->create(['avatar' => 'unicorn']);

        $this->actingAs($child, 'child')->get(route('child.home'))->assertOk()->assertSee('🦄');
    }
}
