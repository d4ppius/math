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

    public function test_the_icon_has_the_requested_size_and_differs_per_child_colour_and_initial(): void
    {
        $mia = Child::factory()->create(['name' => 'Mia', 'color_theme' => 'blue']);
        $ben = Child::factory()->create(['name' => 'Ben', 'color_theme' => 'green']);

        $miaIcon = $this->get(route('child.icon', ['child' => $mia, 'size' => 180]))->getContent();
        $benIcon = $this->get(route('child.icon', ['child' => $ben, 'size' => 180]))->getContent();

        $this->assertSame([180, 180], array_slice(getimagesizefromstring($miaIcon), 0, 2));
        $this->assertNotSame($miaIcon, $benIcon);

        $this->assertSame([32, 32], array_slice(getimagesizefromstring(
            $this->get(route('child.icon', ['child' => $mia, 'size' => 32]))->getContent()
        ), 0, 2));
    }

    /**
     * Regression guard: this route must never live under /icons/. On a stock
     * Debian/Ubuntu Apache install, `Alias /icons/ "/usr/share/apache2/icons/"`
     * (mod_alias's default config for its own directory-listing icons) is
     * resolved before the request reaches .htaccess/PHP at all, so a route
     * under that prefix 404s regardless of how correctly Laravel is
     * configured — confirmed on this app's own production server.
     */
    public function test_the_icon_url_does_not_live_under_the_reserved_apache_icons_alias(): void
    {
        $child = Child::factory()->create();

        $url = route('child.icon', ['child' => $child, 'size' => 180]);

        $this->assertStringNotContainsString('/icons/', parse_url($url, PHP_URL_PATH));
    }
}
