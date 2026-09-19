<?php

namespace Tests\Feature\Public;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    private function configureLegal(): void
    {
        config([
            'legal.name' => 'Muster Informatik',
            'legal.street' => 'Beispielweg 1',
            'legal.zip_city' => '8000 Zürich',
            'legal.country' => 'Schweiz',
            'legal.email' => 'hallo@muster.example',
            'legal.phone' => '+41 44 000 00 00',
            'legal.uid' => 'CHE-123.456.789',
            'legal.responsible' => 'Max Muster',
            'legal.hoster' => 'Beispiel Hosting AG, Schweiz',
        ]);
    }

    public function test_the_imprint_shows_the_details_from_the_configuration(): void
    {
        $this->configureLegal();

        $this->get(route('legal.imprint'))
            ->assertOk()
            ->assertSee('Impressum')
            ->assertSee('Muster Informatik')
            ->assertSee('Beispielweg 1')
            ->assertSee('8000 Zürich')
            ->assertSee('Vertretungsberechtigt: Max Muster')
            ->assertSee('+41 44 000 00 00')
            ->assertSee('CHE-123.456.789');
    }

    public function test_the_email_address_is_not_written_out_as_a_string_in_the_markup(): void
    {
        $this->configureLegal();

        $html = $this->get(route('legal.imprint'))->getContent();

        $this->assertStringNotContainsString('hallo@muster.example', $html);
        $this->assertStringContainsString('hallo [at] muster [dot] example', $html);
    }

    public function test_missing_required_details_show_a_visible_hint_instead_of_silently_disappearing(): void
    {
        config(['legal.name' => null, 'legal.street' => null, 'legal.zip_city' => null, 'legal.email' => null, 'legal.phone' => null, 'legal.uid' => null]);

        $this->get(route('legal.imprint'))
            ->assertOk()
            ->assertSee('[LEGAL_NAME fehlt in der .env]')
            ->assertSee('[LEGAL_STREET fehlt in der .env]')
            ->assertSee('[LEGAL_EMAIL fehlt in der .env]')
            ->assertDontSee('Telefon:')
            ->assertDontSee('Unternehmens-Identifikationsnummer');
    }

    public function test_the_privacy_statement_names_the_operator_the_hoster_and_the_retention_period(): void
    {
        $this->configureLegal();
        config(['contact.retention_months' => 12]);

        $this->get(route('legal.privacy'))
            ->assertOk()
            ->assertSee('Datenschutzerklärung')
            ->assertSee('Muster Informatik')
            ->assertSee('Beispiel Hosting AG, Schweiz')
            ->assertSee('nach 12 Monaten')
            ->assertSee('Kein Tracking, keine Werbung');
    }

    public function test_the_legal_links_are_reachable_from_login_registration_and_the_parent_area(): void
    {
        foreach ([route('login'), route('register')] as $url) {
            $this->get($url)->assertOk()
                ->assertSee(route('legal.imprint'), false)
                ->assertSee(route('legal.privacy'), false)
                ->assertSee(route('contact.show'), false);
        }

        $this->actingAs(User::factory()->create())->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('legal.imprint'), false)
            ->assertSee(route('legal.privacy'), false);
    }

    public function test_the_public_header_offers_login_and_registration_or_the_dashboard(): void
    {
        $this->get(route('legal.imprint'))->assertSee('Anmelden')->assertSee('Registrieren')->assertDontSee('Zum Dashboard');

        $this->actingAs(User::factory()->create())->get(route('legal.imprint'))->assertSee('Zum Dashboard')->assertDontSee('Registrieren');
    }
}
