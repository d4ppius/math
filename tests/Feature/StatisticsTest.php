<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\ChildFactStat;
use App\Models\ExerciseType;
use App\Models\Fact;
use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatisticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_parent_can_see_the_heatmap_for_their_own_child(): void
    {
        $family = Family::factory()->create();
        $user = User::factory()->for($family)->create();
        $child = Child::factory()->for($family)->create();

        $exerciseType = ExerciseType::create(['key' => 'multiplication', 'name' => 'Einmaleins']);
        $fact = Fact::create([
            'exercise_type_id' => $exerciseType->id,
            'operand_a' => 2,
            'operand_b' => 4,
            'correct_answer' => 8,
            'difficulty_group' => 2,
        ]);

        ChildFactStat::create([
            'child_id' => $child->id,
            'fact_id' => $fact->id,
            'attempts_total' => 10,
            'attempts_correct' => 9,
            'avg_response_ms' => 2000,
            'priority_score' => 0.2,
        ]);

        $response = $this->actingAs($user)->get(route('parent.children.statistics', $child));

        $response->assertOk()->assertSee('90%');
    }

    public function test_a_parent_cannot_see_another_familys_child_statistics(): void
    {
        $familyA = Family::factory()->create();
        $familyB = Family::factory()->create();
        $userOfFamilyB = User::factory()->for($familyB)->create();
        $childOfFamilyA = Child::factory()->for($familyA)->create();

        $this->actingAs($userOfFamilyB)
            ->get(route('parent.children.statistics', $childOfFamilyA))
            ->assertForbidden();
    }
}
