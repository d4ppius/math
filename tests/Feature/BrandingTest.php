<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_icon_in_the_parent_manifest_exists_with_the_declared_size(): void
    {
        $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true);

        $this->assertSame('Rechenfuchs', $manifest['name']);
        $this->assertNotEmpty($manifest['icons']);

        foreach ($manifest['icons'] as $icon) {
            $file = public_path(ltrim($icon['src'], '/'));

            $this->assertFileExists($file);
            [$width, $height] = getimagesize($file);
            $this->assertSame($icon['sizes'], "{$width}x{$height}", $icon['src']);
        }
    }

    public function test_the_static_brand_assets_exist(): void
    {
        foreach (['images/logo.png', 'images/icons/apple-touch-icon.png', 'images/icons/icon-base.png', 'favicon.ico'] as $path) {
            $this->assertGreaterThan(0, filesize(public_path($path)), $path);
        }

        $this->assertSame([180, 180], array_slice(getimagesize(public_path('images/icons/apple-touch-icon.png')), 0, 2));
    }

    public function test_icons_that_must_be_opaque_have_no_transparent_corners(): void
    {
        // iOS renders transparency in apple-touch-icons black, and maskable
        // icons need a full-bleed background.
        foreach (['apple-touch-icon', 'icon-base', 'icon-maskable-192', 'icon-maskable-512'] as $name) {
            $image = imagecreatefrompng(public_path("images/icons/{$name}.png"));
            $last = imagesx($image) - 1;

            foreach ([[0, 0], [$last, 0], [0, $last], [$last, $last]] as [$x, $y]) {
                $this->assertSame(0, (imagecolorat($image, $x, $y) >> 24) & 127, "{$name} corner {$x},{$y} is transparent");
            }
        }
    }

    public function test_the_login_page_shows_the_logo_and_the_landing_page_the_icon(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('images/logo.png')
            ->assertSee('images/icons/apple-touch-icon.png');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('images/icons/icon-512.png')
            ->assertSee('Rechenfuchs');
    }
}
