<?php

namespace Tests\Feature;

use App\Models\Child;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChildIconTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renders_a_png_icon_for_a_child(): void
    {
        $child = Child::factory()->create(['name' => 'Mia', 'color_theme' => 'blue']);

        $response = $this->get(route('child.icon', ['child' => $child, 'size' => 180]));

        $response->assertOk();
        $this->assertSame('image/png', $response->headers->get('Content-Type'));
    }
}
