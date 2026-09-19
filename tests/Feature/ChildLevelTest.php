<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChildLevelTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_child_home_shows_level_title_and_progress(): void
    {
        $child = Child::factory()->create(['total_points' => 800]);

        $this->actingAs($child, 'child')
            ->get(route('child.home'))
            ->assertOk()
            ->assertSee('Level 2')
            ->assertSee('Zahlen-Entdecker')
            ->assertSee('Noch 400 Punkte bis Level 3')
            ->assertSee('width: 50%', false);
    }

    public function test_the_top_level_shows_a_congratulation_instead_of_a_next_level(): void
    {
        $child = Child::factory()->create(['total_points' => 31000]);

        $this->actingAs($child, 'child')
            ->get(route('child.home'))
            ->assertSee('Level 10')
            ->assertSee('Höchstes Level erreicht!')
            ->assertDontSee('Punkte bis Level');
    }

    public function test_parents_see_the_level_on_the_statistics_page(): void
    {
        $family = Family::factory()->create();
        $user = User::factory()->for($family)->create();
        $child = Child::factory()->for($family)->create(['total_points' => 1300]);

        $this->actingAs($user)
            ->get(route('parent.children.statistics', $child))
            ->assertOk()
            ->assertSee('Level 3')
            ->assertSee('Rechen-Lehrling')
            ->assertSee('1300 Punkte insgesamt');
    }
}
