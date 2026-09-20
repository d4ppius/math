<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\Badge;
use App\Models\Child;
use App\Models\ExerciseType;
use App\Models\Fact;
use App\Services\ExerciseTypes\ExerciseTypeRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class StatisticsController extends Controller
{
    public function show(Request $request, Child $child, ExerciseTypeRegistry $registry): View
    {
        $this->authorize('view', $child);

        $heatmaps = $this->heatmaps($child, $registry);

        $weeklyPoints = $this->weeklyPoints($child);
        $goalStreak = $this->goalStreak($child);

        return view('parent.children.statistics', [
            'child' => $child,
            'heatmaps' => $heatmaps,
            'weeklyPoints' => $weeklyPoints,
            'goalStreak' => $goalStreak,
            'badges' => Badge::orderBy('id')->get(),
            'earnedBadges' => $child->badges->keyBy('id'),
        ]);
    }

    /**
     * One heatmap per exercise: those switched on, plus any with practice
     * history (also switched-off ones or ones without a settings row), so
     * nothing the child did disappears.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function heatmaps(Child $child, ExerciseTypeRegistry $registry): Collection
    {
        $settings = $child->exerciseSettings()->get()->keyBy('exercise_type_id');

        return ExerciseType::whereIn('key', array_keys(config('exercise_types', [])))->orderBy('id')->get()
            ->map(function (ExerciseType $type) use ($child, $registry, $settings) {
                $setting = $settings->get($type->id);

                $stats = $child->factStats()
                    ->whereHas('fact', fn ($query) => $query->where('exercise_type_id', $type->id))
                    ->with('fact')
                    ->get()
                    ->keyBy(fn ($stat) => $stat->fact->operand_a.'-'.$stat->fact->operand_b);

                if (! $setting?->enabled && $stats->isEmpty()) {
                    return null;
                }

                $implementation = $registry->get($type->key);
                $grid = $implementation->gridDefinition();

                return [
                    'key' => $type->key,
                    'label' => $implementation->label(),
                    // No settings row at all (legacy data): don't claim the exercise is off.
                    'enabled' => $setting?->enabled ?? true,
                    'operator' => $implementation->operator(),
                    'rows' => $grid['rows'],
                    'cols' => $grid['cols'],
                    'cells' => $stats,
                    'facts' => Fact::where('exercise_type_id', $type->id)->get()->keyBy(fn ($fact) => $fact->operand_a.'-'.$fact->operand_b),
                    'implementation' => $implementation,
                ];
            })
            ->filter()
            ->values();
    }

    /** @return array<int, array{date: Carbon, label: string, points: int}> */
    private function weeklyPoints(Child $child): array
    {
        $since = now()->subDays(6)->startOfDay();

        $byDay = $child->practiceSessions()
            ->where('status', 'completed')
            ->where('started_at', '>=', $since)
            ->get()
            ->groupBy(fn ($session) => $session->started_at->toDateString());

        $days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $key = $date->toDateString();

            $days[] = [
                'date' => $date,
                'label' => $date->isoFormat('dd'),
                'points' => $byDay->get($key, collect())->sum('total_points'),
            ];
        }

        return $days;
    }

    /** @return array<int, array{date: Carbon, label: string, met: bool}> */
    private function goalStreak(Child $child): array
    {
        $since = now()->subDays(6)->startOfDay();

        $logs = $child->dailyGoalLogs()
            ->where('date', '>=', $since->toDateString())
            ->get()
            ->keyBy(fn ($log) => $log->date->toDateString());

        $days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $key = $date->toDateString();

            $days[] = [
                'date' => $date,
                'label' => $date->isoFormat('dd'),
                'met' => (bool) ($logs->get($key)?->goal_met),
            ];
        }

        return $days;
    }
}
