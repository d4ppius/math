<?php

namespace Database\Seeders;

use App\Models\Badge;
use Illuminate\Database\Seeder;

/**
 * The fixed badge catalogue. Idempotent (keyed by `key`), so it is safe to
 * run on every deploy. `criteria.type` selects the rule in BadgeEvaluator.
 *
 * `icon` is an emoji shown until artwork exists: dropping a PNG named
 * public/images/badges/{key}.png (or {criteria.type}.png as a shared image
 * for a whole family of badges) replaces it without any code change.
 */
class BadgeSeeder extends Seeder
{
    public function run(): void
    {
        $badges = [
            [
                'key' => 'first_session',
                'name' => 'Erste Übung',
                'description' => 'Du hast deine erste Übung geschafft!',
                'icon' => '🎉',
                'criteria' => ['type' => 'first_session', 'min_questions' => 5],
            ],
            [
                'key' => 'streak_7',
                'name' => '7-Tage-Serie',
                'description' => '7 Tage hintereinander das Tagesziel erreicht.',
                'icon' => '🔥',
                'criteria' => ['type' => 'streak_days', 'days' => 7],
            ],
            [
                'key' => 'blitz',
                'name' => 'Blitzrechner',
                'description' => 'In einer Übung mindestens 10 richtige Antworten, im Schnitt unter 2 Sekunden.',
                'icon' => '⚡',
                'criteria' => ['type' => 'blitz', 'min_correct_in_session' => 10, 'max_avg_response_ms' => 2000],
            ],
        ];

        foreach (range(1, 9) as $row) {
            $badges[] = [
                'key' => "row_mastery_{$row}",
                'name' => "Meister der {$row}er-Reihe",
                'description' => "Die {$row}er-Reihe zu über 90 % richtig gelöst.",
                'icon' => '👑',
                'criteria' => [
                    'type' => 'row_mastery',
                    'exercise_type' => 'multiplication',
                    'difficulty_group' => $row,
                    'min_accuracy' => 0.9,
                    'min_attempts' => 15,
                    'min_facts' => 8,
                ],
            ];
        }

        foreach ($badges as $badge) {
            Badge::updateOrCreate(['key' => $badge['key']], $badge);
        }
    }
}
