<?php

namespace Tests\Feature;

use App\Models\Child;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChildManifestTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_manifest_start_url_is_the_childs_own_magic_link(): void
    {
        $child = Child::factory()->create(['name' => 'Nina']);
        $plainToken = $child->generateLoginToken();

        $response = $this->getJson(route('child.manifest', ['token' => $plainToken]));

        $response->assertOk();
        $response->assertJson([
            'name' => 'Nina',
            'start_url' => route('child.magic-link', ['token' => $plainToken]),
        ]);
    }

    public function test_an_invalid_token_returns_404(): void
    {
        $this->getJson(route('child.manifest', ['token' => 'does-not-exist']))->assertNotFound();
    }

    public function test_the_magic_link_page_links_its_own_manifest(): void
    {
        $child = Child::factory()->create();
        $plainToken = $child->generateLoginToken();

        $this->get('/k/'.$plainToken)
            ->assertOk()
            ->assertSee(route('child.manifest', ['token' => $plainToken]), false);
    }

    public function test_a_plain_kind_visit_does_not_link_a_manifest(): void
    {
        $child = Child::factory()->create();

        $this->actingAs($child, 'child')
            ->get(route('child.home'))
            ->assertOk()
            ->assertDontSee('rel="manifest"', false);
    }
}
