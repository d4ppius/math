<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\Badge;
use App\Models\Child;
use App\Models\ExerciseType;
use App\Services\ExerciseTypes\ExerciseTypeRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class StatisticsController extends Controller
{
    public function show(Request $request, Child $child, ExerciseTypeRegistry $registry): View
    {
        $this->authorize('view', $child);

        $exerciseType = ExerciseType::where('key', 'multiplication')->first();
        $implementation = $exerciseType ? $registry->get($exerciseType->key) : null;

        $heatmap = null;

        if ($exerciseType && $implementation) {
            $grid = $implementation->gridDefinition();

            $stats = $child->factStats()
                ->whereHas('fact', fn ($query) => $query->where('exercise_type_id', $exerciseType->id))
                ->with('fact')
                ->get()
                ->keyBy(fn ($stat) => $stat->fact->operand_a.'-'.$stat->fact->operand_b);

            $heatmap = [
                'label' => $implementation->label(),
                'rows' => $grid['rows'],
                'cols' => $grid['cols'],
                'cells' => $stats,
            ];
        }

        $weeklyPoints = $this->weeklyPoints($child);
        $goalStreak = $this->goalStreak($child);

        return view('parent.children.statistics', [
            'child' => $child,
            'heatmap' => $heatmap,
            'weeklyPoints' => $weeklyPoints,
            'goalStreak' => $goalStreak,
            'badges' => Badge::orderBy('id')->get(),
            'earnedBadges' => $child->badges->keyBy('id'),
        ]);
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
