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
                'description' => 'Schaffe deine erste Übung mit mindestens 5 Aufgaben.',
                'icon' => '🎉',
                'criteria' => ['type' => 'first_session', 'min_questions' => 5],
            ],
            [
                'key' => 'streak_7',
                'name' => '7-Tage-Serie',
                'description' => 'Erreiche 7 Tage hintereinander dein Tagesziel.',
                'icon' => '🔥',
                'criteria' => ['type' => 'streak_days', 'days' => 7],
            ],
            [
                'key' => 'blitz',
                'name' => 'Blitzrechner',
                'description' => 'Gib in einer Einmaleins-Übung mindestens 10 richtige Antworten, im Schnitt unter 2 Sekunden.',
                'icon' => '⚡',
                'criteria' => ['type' => 'blitz', 'exercise_type' => 'multiplication', 'min_correct_in_session' => 10, 'max_avg_response_ms' => 2000],
            ],
        ];

        // One badge per level 2..10 (title and emoji from LevelCalculator),
        // earned once any exercise reaches that level — a steady stream of
        // collectibles alongside the per-exercise level bars.
        $levels = [
            2 => ['Zahlen-Entdecker', '🔍'],
            3 => ['Rechen-Lehrling', '📘'],
            4 => ['Zahlen-Flitzer', '🏃'],
            5 => ['Rechen-Profi', '⭐'],
            6 => ['Knobel-Meister', '🧩'],
            7 => ['Zahlen-Zauberer', '🪄'],
            8 => ['Rechen-Ass', '🎯'],
            9 => ['Mathe-Held', '🦸'],
            10 => ['Rechenfuchs-Meister', '🦊'],
        ];

        foreach ($levels as $level => [$title, $emoji]) {
            $badges[] = [
                'key' => "level_{$level}",
                'name' => $title,
                'description' => "Erreiche Level {$level} in einer Übung.",
                'icon' => $emoji,
                'criteria' => ['type' => 'level_reached', 'level' => $level],
            ];
        }

        foreach (range(1, 9) as $row) {
            $badges[] = [
                'key' => "row_mastery_{$row}",
                'name' => "Meister der {$row}er-Reihe",
                'description' => "Löse die {$row}er-Reihe zu über 90 % richtig.",
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

        // Plus bis 20: a starter badge, one master badge per group, a speed badge.
        $plusGroups = [
            1 => ['name' => 'Meister von Plus bis 10', 'text' => '«Plus bis 10»', 'overlay' => '10', 'attempts' => 50, 'facts' => 34],
            2 => ['name' => 'Meister von Plus mit der 10', 'text' => '«Plus mit der 10»', 'overlay' => '+10', 'attempts' => 22, 'facts' => 14],
            3 => ['name' => 'Meister des Zehnerübergangs', 'text' => 'den «Zehnerübergang»', 'overlay' => '20', 'attempts' => 40, 'facts' => 27],
        ];

        $badges[] = [
            'key' => 'addition_first_session',
            'name' => 'Plus-Starter',
            'description' => 'Schaffe deine erste Plus-Übung mit mindestens 5 Aufgaben.',
            'icon' => '➕',
            'criteria' => ['type' => 'first_session', 'exercise_type' => 'addition', 'min_questions' => 5],
        ];

        foreach ($plusGroups as $group => $plus) {
            $badges[] = [
                'key' => "addition_mastery_{$group}",
                'name' => $plus['name'],
                'description' => "Löse {$plus['text']} zu über 90 % richtig.",
                'icon' => '🥇',
                'criteria' => [
                    'type' => 'group_mastery',
                    'exercise_type' => 'addition',
                    'difficulty_group' => $group,
                    'overlay' => $plus['overlay'],
                    'min_accuracy' => 0.9,
                    'min_attempts' => $plus['attempts'],
                    'min_facts' => $plus['facts'],
                ],
            ];
        }

        $badges[] = [
            'key' => 'addition_blitz',
            'name' => 'Plus-Blitz',
            'description' => 'Gib in einer Plus-Übung mindestens 10 richtige Antworten, im Schnitt unter 1,5 Sekunden.',
            'icon' => '⚡',
            'criteria' => ['type' => 'blitz', 'exercise_type' => 'addition', 'min_correct_in_session' => 10, 'max_avg_response_ms' => 1500],
        ];

        // Minus bis 20: mirrors the Plus badges (starter, one master per group, speed).
        $minusGroups = [
            1 => ['name' => 'Meister von Minus bis 10', 'text' => '«Minus bis 10»', 'overlay' => '10', 'attempts' => 50, 'facts' => 34],
            2 => ['name' => 'Meister von Minus mit der 10', 'text' => '«Minus mit der 10»', 'overlay' => '−10', 'attempts' => 32, 'facts' => 22],
            3 => ['name' => 'Meister des Minus-Zehnerübergangs', 'text' => 'den «Zehnerübergang» beim Minus', 'overlay' => '20', 'attempts' => 40, 'facts' => 27],
        ];

        $badges[] = [
            'key' => 'subtraction_first_session',
            'name' => 'Minus-Starter',
            'description' => 'Schaffe deine erste Minus-Übung mit mindestens 5 Aufgaben.',
            'icon' => '➖',
            'criteria' => ['type' => 'first_session', 'exercise_type' => 'subtraction', 'min_questions' => 5],
        ];

        foreach ($minusGroups as $group => $minus) {
            $badges[] = [
                'key' => "subtraction_mastery_{$group}",
                'name' => $minus['name'],
                'description' => "Löse {$minus['text']} zu über 90 % richtig.",
                'icon' => '🥇',
                'criteria' => [
                    'type' => 'group_mastery',
                    'exercise_type' => 'subtraction',
                    'difficulty_group' => $group,
                    'overlay' => $minus['overlay'],
                    'min_accuracy' => 0.9,
                    'min_attempts' => $minus['attempts'],
                    'min_facts' => $minus['facts'],
                ],
            ];
        }

        $badges[] = [
            'key' => 'subtraction_blitz',
            'name' => 'Minus-Blitz',
            'description' => 'Gib in einer Minus-Übung mindestens 10 richtige Antworten, im Schnitt unter 1,5 Sekunden.',
            'icon' => '⚡',
            'criteria' => ['type' => 'blitz', 'exercise_type' => 'subtraction', 'min_correct_in_session' => 10, 'max_avg_response_ms' => 1500],
        ];

        // Verdoppeln & Halbieren: a starter, one master per operation (each
        // spanning both number-range groups of that operation), and a speed badge.
        $badges[] = [
            'key' => 'doubling_first_session',
            'name' => 'Verdoppeln-Starter',
            'description' => 'Schaffe deine erste Übung im Verdoppeln & Halbieren mit mindestens 5 Aufgaben.',
            'icon' => '🔁',
            'criteria' => ['type' => 'first_session', 'exercise_type' => 'doubling', 'min_questions' => 5],
        ];

        $badges[] = [
            'key' => 'doubling_mastery',
            'name' => 'Meister im Verdoppeln',
            'description' => 'Verdopple sicher, auch bis 50, zu über 90 % richtig.',
            'icon' => '🥇',
            'criteria' => [
                'type' => 'group_mastery',
                'exercise_type' => 'doubling',
                'difficulty_groups' => [1, 2],
                'overlay' => '·2',
                'min_accuracy' => 0.9,
                'min_attempts' => 30,
                'min_facts' => 18,
            ],
        ];

        $badges[] = [
            'key' => 'halving_mastery',
            'name' => 'Meister im Halbieren',
            'description' => 'Halbiere sicher, auch bis 50, zu über 90 % richtig.',
            'icon' => '🥇',
            'criteria' => [
                'type' => 'group_mastery',
                'exercise_type' => 'doubling',
                'difficulty_groups' => [3, 4],
                'overlay' => '½',
                'min_accuracy' => 0.9,
                'min_attempts' => 30,
                'min_facts' => 18,
            ],
        ];

        $badges[] = [
            'key' => 'doubling_blitz',
            'name' => 'Verdoppeln-Blitz',
            'description' => 'Gib beim Verdoppeln & Halbieren mindestens 10 richtige Antworten, im Schnitt unter 1,5 Sekunden.',
            'icon' => '⚡',
            'criteria' => ['type' => 'blitz', 'exercise_type' => 'doubling', 'min_correct_in_session' => 10, 'max_avg_response_ms' => 1500],
        ];

        // Freunde vom Fuchs: one animal per weekday, earned by practising on
        // that day. Each animal's image also carries a math sign (like the fox
        // wears "+"), but that is only artwork — the rule is the weekday.
        $weekdayFriends = [
            1 => ['Igel', '🦔'],
            2 => ['Hund', '🐶'],
            3 => ['Pferd', '🐴'],
            4 => ['Katze', '🐱'],
            5 => ['Hase', '🐰'],
            6 => ['Elefant', '🐘'],
            7 => ['Faultier', '🦥'],
        ];
        $weekdayNames = [1 => 'Montag', 2 => 'Dienstag', 3 => 'Mittwoch', 4 => 'Donnerstag', 5 => 'Freitag', 6 => 'Samstag', 7 => 'Sonntag'];

        foreach ($weekdayFriends as $day => [$animal, $emoji]) {
            $badges[] = [
                'key' => "weekday_{$day}",
                'name' => $animal,
                'description' => "Übe an einem {$weekdayNames[$day]} mindestens 5 Aufgaben.",
                'icon' => $emoji,
                'criteria' => ['type' => 'weekday', 'weekday' => $day, 'min_questions' => 5],
            ];
        }

        $badges[] = [
            'key' => 'allrounder',
            'name' => 'Allrounder',
            'description' => 'Übe am selben Tag mit mindestens zwei Übungen.',
            'icon' => '🌟',
            'criteria' => ['type' => 'all_exercises_same_day', 'min_exercises' => 2, 'min_questions' => 5],
        ];

        foreach ($badges as $badge) {
            Badge::updateOrCreate(['key' => $badge['key']], $badge);
        }
    }
}
