<?php

namespace Tests\Feature\Public;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_landing_page_explains_how_it_works_and_leads_to_registration_and_login(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Einmaleins üben, das Kindern', false)
            ->assertSee('So funktioniert', false)
            ->assertSee('id="so-gehts"', false)
            ->assertSee('id="funktionen"', false)
            ->assertSee('id="faq"', false)
            ->assertSee('Familie anlegen')
            ->assertSee('Das Icon aufs Tablet holen')
            ->assertSee('Häufige Fragen')
            ->assertSee(route('register'), false)
            ->assertSee(route('login'), false)
            ->assertSee(route('contact.show'), false)
            ->assertSee(route('legal.imprint'), false)
            ->assertSee(route('legal.privacy'), false);
    }

    public function test_logged_in_parents_are_offered_the_dashboard_instead_of_registration(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Zum Dashboard')
            ->assertDontSee('Jetzt registrieren');
    }

    public function test_the_page_is_indexable_and_has_a_description_and_share_preview(): void
    {
        $this->get(route('home'))
            ->assertDontSee('name="robots"', false)
            ->assertSee('<title>Einmaleins üben für Kinder · Rechenfuchs</title>', false)
            ->assertSee('name="description"', false)
            ->assertSee('property="og:image"', false)
            ->assertSee('images/logo.png', false);
    }

    public function test_every_screenshot_exists_and_carries_its_size_so_the_layout_does_not_jump(): void
    {
        $html = $this->get(route('home'))->getContent();

        foreach (['child-home', 'child-achievements', 'child-practice', 'parent-settings', 'parent-statistics'] as $name) {
            $this->assertFileExists(public_path("images/landing/{$name}.webp"));
            $this->assertStringContainsString("images/landing/{$name}.webp", $html);
        }

        preg_match_all('/<img[^>]*images\/landing[^>]*>/s', $html, $images);
        $this->assertNotEmpty($images[0]);

        foreach ($images[0] as $tag) {
            $this->assertMatchesRegularExpression('/\swidth="\d+"/', $tag);
            $this->assertMatchesRegularExpression('/\sheight="\d+"/', $tag);
            $this->assertMatchesRegularExpression('/\salt="[^"]+"/', $tag, 'meaningful alt text');
        }
    }

    public function test_the_faq_answers_the_questions_a_parent_would_ask(): void
    {
        $this->get(route('home'))
            ->assertSee('Braucht mein Kind ein Konto oder eine E-Mail-Adresse?')
            ->assertSee('Wie bekomme ich das Icon auf das iPad?')
            ->assertSee('Was, wenn mein Kind der Timer stresst?')
            ->assertSee('Kann ich meine Daten löschen?')
            ->assertSee('<details', false);
    }

    public function test_the_child_safety_promises_match_what_the_app_does(): void
    {
        // The claims are backed elsewhere: no third-party assets (BrandingTest),
        // deletion of the whole family (AccountDeletionTest), no child credentials.
        $this->get(route('home'))
            ->assertSee('Keine Konten für Kinder')
            ->assertSee('Keine Werbung, kein Tracking')
            ->assertSee(route('legal.privacy'), false);
    }
}
