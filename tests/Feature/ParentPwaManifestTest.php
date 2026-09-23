<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Unlike a child (whose manifest must be dynamic — each one's start_url is
 * their own magic link, see ChildManifestController), every parent shares the
 * same icon, name and start_url, so one static public/manifest.webmanifest is
 * enough. This only needs to be *linked* from every page a parent might
 * realistically use "Zum Home-Bildschirm hinzufügen" from — which used to be
 * just the authenticated dashboard, not the landing page or the login/register
 * screens where a parent is more likely to actually do that.
 */
class ParentPwaManifestTest extends TestCase
{
    use RefreshDatabase;

    /** @return list<string> */
    public static function installablePages(): array
    {
        return [
            'Startseite' => ['home'],
            'Anmelden' => ['login'],
            'Registrieren' => ['register'],
        ];
    }

    #[DataProvider('installablePages')]
    public function test_the_manifest_and_home_screen_meta_tags_are_present(string $route): void
    {
        $html = $this->get(route($route))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'rel="manifest"'), 'exactly one manifest link');
        $this->assertStringContainsString('manifest.webmanifest', $html);
        $this->assertStringContainsString('apple-mobile-web-app-capable" content="yes"', $html);
        $this->assertStringContainsString('apple-mobile-web-app-title" content="Rechenfuchs"', $html);
        $this->assertStringContainsString('rel="apple-touch-icon"', $html);
    }

    public function test_the_dashboard_still_has_exactly_one_manifest_link_not_two(): void
    {
        $html = $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'rel="manifest"'));
        $this->assertStringContainsString('manifest.webmanifest', $html);
        $this->assertStringContainsString('apple-mobile-web-app-capable" content="yes"', $html);
    }

    public function test_the_manifest_scope_covers_the_whole_site_not_only_eltern(): void
    {
        $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true);

        $this->assertSame('/', $manifest['scope']);
        // start_url must stay inside scope, and it's what the installed icon
        // opens — logged out, Laravel just redirects it straight to the login.
        $this->assertSame('/eltern/dashboard', $manifest['start_url']);
    }

    public function test_a_logged_in_childs_daily_home_screen_still_has_no_manifest_of_its_own(): void
    {
        // Regression guard for the existing per-child manifest behaviour: a
        // returning child (no token in this request) must not pick up the
        // parent's manifest either.
        $child = Child::factory()->create();

        $this->actingAs($child, 'child')
            ->get(route('child.home'))
            ->assertOk()
            ->assertDontSee('rel="manifest"', false);
    }
}
