<?php

namespace App\Services\Statistics;

use App\Models\Child;
use App\Models\PracticeSession;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The numbers behind the parents' round statistics: correct and wrong tasks per
 * day, the pace in correct answers per minute and the most recent rounds, for
 * all exercises together and for each exercise on its own.
 *
 * Pace is measured from the start of a session to its last answer, not to
 * `ended_at`: a session closed automatically by sessions:close-expired ends at
 * the moment of the cron run, not when the child stopped practising.
 */
class SessionTrends
{
    public const DAYS = 60;

    public const MIN_ANSWERED_FOR_TEMPO = 5;

    public const RECENT_ROUNDS = 10;

    private const WEEK = 7;

    private const MONTH = 30;

    /**
     * @param  array<string, string>  $exercises  exercise key => label, in display order
     * @return array{days: list<array{date: string, weekday: string, dm: string}>, scopes: array<string, array<string, mixed>>}
     */
    public function build(Child $child, array $exercises): array
    {
        $today = now()->startOfDay();

        $days = [];
        for ($i = self::DAYS - 1; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);
            $days[] = ['date' => $date->toDateString(), 'weekday' => $date->isoFormat('dd'), 'dm' => $date->format('d.m.')];
        }

        $rows = $this->rows($child, $exercises, $today->copy()->subDays(self::DAYS - 1));

        $scopes = ['all' => $this->scope($rows, $days, withTempo: false)];
        foreach (array_keys($exercises) as $key) {
            $scopes[$key] = $this->scope($rows->where('key', $key)->values(), $days, withTempo: true);
        }

        return ['days' => $days, 'scopes' => $scopes];
    }

    /** One entry per finished, non-preview session with at least one answer, oldest first. */
    private function rows(Child $child, array $exercises, Carbon $since): Collection
    {
        return $child->practiceSessions()
            ->where('status', 'completed')
            ->where('is_preview', false)
            ->where('questions_answered', '>', 0)
            ->where('started_at', '>=', $since)
            ->with('exerciseType')
            ->withMax('attempts as last_answered_at', 'answered_at')
            ->orderBy('started_at')
            ->orderBy('id')
            ->get()
            ->map(function (PracticeSession $session) use ($exercises) {
                $key = $session->exerciseType->key;

                return [
                    'id' => $session->id,
                    'key' => $key,
                    'label' => $exercises[$key] ?? $session->exerciseType->name,
                    'started_at' => $session->started_at,
                    'date' => $session->started_at->toDateString(),
                    'answered' => (int) $session->questions_answered,
                    'correct' => (int) $session->questions_correct,
                    'minutes' => $this->paceMinutes($session),
                ];
            });
    }

    /** Minutes the pace is measured over, or null when the session is too short to say anything. */
    private function paceMinutes(PracticeSession $session): ?float
    {
        if ($session->questions_answered < self::MIN_ANSWERED_FOR_TEMPO || ! $session->last_answered_at) {
            return null;
        }

        $seconds = $session->started_at->diffInSeconds(Carbon::parse($session->last_answered_at), false);

        return $seconds >= 1 ? $seconds / 60 : null;
    }

    /** @return array<string, mixed> */
    private function scope(Collection $rows, array $days, bool $withTempo): array
    {
        $byDate = $rows->groupBy('date');

        $correct = $wrong = $tempo = [];
        foreach ($days as $day) {
            $sessions = $byDate->get($day['date'], collect());
            $correct[] = $sessions->sum('correct');
            $wrong[] = $sessions->sum('answered') - $sessions->sum('correct');
            $tempo[] = $withTempo ? $this->pace($sessions) : null;
        }

        return [
            'correct' => $correct,
            'wrong' => $wrong,
            'tempo' => $tempo,
            'trend' => $withTempo ? $this->trends($rows, $days) : null,
            'rounds' => $rows->reverse()->take(self::RECENT_ROUNDS)->values()->map(fn (array $row) => [
                'id' => $row['id'],
                'when' => $row['started_at']->format('d.m. H:i'),
                'exercise' => $row['label'],
                'answered' => $row['answered'],
                'correct' => $row['correct'],
                'wrong' => $row['answered'] - $row['correct'],
                'accuracy' => (int) round($row['correct'] / $row['answered'] * 100),
                'tempo' => $row['minutes'] ? round($row['correct'] / $row['minutes'], 1) : null,
            ])->all(),
        ];
    }

    /** Correct answers per minute over the sessions that are long enough, weighted by time. */
    private function pace(Collection $sessions): ?float
    {
        $measured = $sessions->filter(fn (array $row) => $row['minutes'] !== null);
        $minutes = $measured->sum('minutes');

        return $minutes > 0 ? round($measured->sum('correct') / $minutes, 1) : null;
    }

    /**
     * Average pace of the latest window against the window before it, for a
     * week (7 days) and a month (30 days).
     *
     * @return array<string, array{current: float|null, previous: float|null}>
     */
    private function trends(Collection $rows, array $days): array
    {
        $dates = array_column($days, 'date');
        $trend = [];

        foreach (['week' => self::WEEK, 'month' => self::MONTH] as $name => $length) {
            $current = array_slice($dates, -$length);
            $previous = array_slice($dates, -2 * $length, $length);

            $trend[$name] = [
                'current' => $this->pace($rows->whereIn('date', $current)),
                'previous' => $this->pace($rows->whereIn('date', $previous)),
            ];
        }

        return $trend;
    }
}
