<?php

namespace Tests\Feature\Public;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuideTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_guide_explains_points_levels_badges_and_reminders(): void
    {
        $this->get(route('guide'))
            ->assertOk()
            ->assertDontSee('name="robots"', false)
            // Punkte
            ->assertSee('10 Grundpunkte')
            ->assertSee('erste Sekunde ist dabei immer geschenkt')
            // Level: all ten, first and last by name
            ->assertSee('Level 1')
            ->assertSee('Rechen-Anfänger')
            ->assertSee('Level 10')
            ->assertSee('Rechenfuchs-Meister')
            ->assertSee('etwa 10 Wochen')
            // Abzeichen
            ->assertSee('18 Abzeichen')
            ->assertSee('Reihen-Meister')
            // Erinnerungen
            ->assertSee('Zeit zum Üben')
            ->assertSee('Tagesziel erreicht')
            // Weitere Einstellungen
            ->assertSee('Rechentipp bei falschen Antworten');
    }

    public function test_the_guide_is_linked_from_the_landing_page_navigation_and_footer(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertSame(4, substr_count($html, route('guide')), 'desktop nav, mobile nav, footer, and the FAQ link');
    }
}
