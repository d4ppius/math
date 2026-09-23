<?php

namespace Tests\Feature\Public;

use App\Models\Child;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_robots_txt_keeps_private_areas_out_but_not_the_public_pages(): void
    {
        $robots = file_get_contents(public_path('robots.txt'));

        foreach (['/k/', '/kind', '/eltern/', '/admin', '/child-icon/', '/manifest/'] as $path) {
            $this->assertStringContainsString("Disallow: {$path}", $robots);
        }

        foreach (['/', '/impressum', '/datenschutz', '/kontakt'] as $publicPath) {
            $this->assertStringNotContainsString("Disallow: {$publicPath}\n", $robots);
        }
    }

    public function test_private_pages_are_marked_noindex(): void
    {
        $child = Child::factory()->create(['login_token_hash' => '']);
        $token = $child->generateLoginToken();

        $this->get(route('login'))->assertSee('content="noindex, nofollow"', false);
        $this->get(route('register'))->assertSee('content="noindex, nofollow"', false);
        $this->get(route('child.magic-link', ['token' => $token]))->assertSee('content="noindex, nofollow"', false);
        $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertSee('content="noindex, nofollow"', false);
    }

    public function test_public_pages_stay_indexable(): void
    {
        foreach ([route('home'), route('legal.imprint'), route('legal.privacy'), route('contact.show')] as $url) {
            $this->get($url)->assertOk()->assertDontSee('name="robots"', false);
        }
    }
}
