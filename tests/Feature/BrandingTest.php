<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\ExerciseType;
use App\Models\PracticeSession;
use App\Services\IconGenerator;
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
            $file = public_path(ltrim(parse_url($icon['src'], PHP_URL_PATH), '/'));

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

    public function test_the_login_page_shows_the_logo_and_the_landing_page_the_mascot(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('images/logo.png')
            ->assertSee('images/icons/apple-touch-icon.png');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('images/mascot.png')
            ->assertSee('Rechenfuchs');
    }

    public function test_the_mascot_is_shown_in_the_child_area(): void
    {
        $child = Child::factory()->create(['login_token_hash' => '']);
        $token = $child->generateLoginToken();

        $this->get(route('child.magic-link', ['token' => $token]))
            ->assertOk()
            ->assertSee('images/mascot.png', false)
            ->assertSee('mascot-float', false);

        $this->actingAs($child, 'child')->get(route('child.home'))->assertSee('images/mascot.png', false);
    }

    public function test_the_mascot_peeks_over_the_card_on_the_pin_page_with_an_explicit_size(): void
    {
        $child = Child::factory()->create(['login_token_hash' => '']);
        $token = $child->generateLoginToken();
        $child->setPin('1234');

        // The size is inline, so it can't blow up to the artwork's full size
        // when the built CSS is stale.
        $this->get(route('child.magic-link', ['token' => $token]))
            ->assertOk()
            ->assertSee('Gib deinen Code ein')
            ->assertSee('images/mascot.png', false)
            ->assertSee('width="150"', false)
            ->assertSee('width:150px', false)
            ->assertSee('margin:0 auto -70px', false);
    }

    public function test_the_mascot_image_is_a_transparent_png_of_a_sensible_size(): void
    {
        [$width, $height] = getimagesize(public_path('images/mascot.png'));

        $this->assertSame(400, $width);
        $this->assertSame(611, $height);
        $this->assertLessThan(400_000, filesize(public_path('images/mascot.png')));

        $image = imagecreatefrompng(public_path('images/mascot.png'));
        $this->assertSame(127, (imagecolorat($image, 0, 0) >> 24) & 127, 'corner should be transparent');
    }

    public function test_the_mascot_reacts_to_answers_on_the_practice_screen(): void
    {
        $child = Child::factory()->create();
        $session = PracticeSession::create([
            'child_id' => $child->id,
            'exercise_type_id' => ExerciseType::create(['key' => 'multiplication', 'name' => 'Einmaleins'])->id,
            'started_at' => now(),
            'planned_duration_seconds' => 600,
            'status' => 'active',
        ]);

        $this->actingAs($child, 'child')
            ->get(route('child.sessions.show', $session))
            ->assertOk()
            ->assertSee('images/icons/icon-192.png', false)
            ->assertSee('mascot-hop', false)
            ->assertSee('mascot-shake', false);
    }

    public function test_the_child_icon_urls_are_versioned_to_bust_stale_ios_caches(): void
    {
        $child = Child::factory()->create(['login_token_hash' => '']);
        $token = $child->generateLoginToken();
        $version = 'v='.IconGenerator::VERSION;

        $this->get(route('child.magic-link', ['token' => $token]))
            ->assertSee("child-icon/{$child->id}/180.png?{$version}", false);

        $icons = $this->getJson(route('child.manifest', ['token' => $token]))->json('icons');
        foreach ($icons as $icon) {
            $this->assertStringContainsString($version, $icon['src']);
        }
    }

    public function test_no_page_loads_fonts_or_other_assets_from_a_third_party(): void
    {
        $child = Child::factory()->create(['login_token_hash' => '']);
        $token = $child->generateLoginToken();

        foreach ([route('home'), route('login'), route('register'), route('child.magic-link', ['token' => $token])] as $url) {
            $this->get($url)->assertOk()->assertDontSee('fonts.bunny.net')->assertDontSee('fonts.googleapis.com');
        }
    }
}
