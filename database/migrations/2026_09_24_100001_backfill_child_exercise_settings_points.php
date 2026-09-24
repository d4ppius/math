<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * One-off data migration, safe to run more than once: fills the new
 * child_exercise_settings.points column from history instead of starting
 * everyone at 0. Every attempt has always incremented
 * practice_sessions.total_points and children.total_points together
 * (AttemptRecorder::record()), so summing sessions per (child, exercise_type)
 * exactly reconstructs how many of a child's points came from which exercise.
 *
 * A session can only ever be created for an exercise the child already has a
 * child_exercise_settings row for (PracticeSessionController::start() checks
 * Child::availableExercises()), so every sum below is guaranteed to find a
 * matching row to update.
 */
return new class extends Migration
{
    public function up(): void
    {
        $sums = DB::table('practice_sessions')
            ->select('child_id', 'exercise_type_id', DB::raw('SUM(total_points) as points'))
            ->groupBy('child_id', 'exercise_type_id')
            ->get();

        foreach ($sums as $row) {
            DB::table('child_exercise_settings')
                ->where('child_id', $row->child_id)
                ->where('exercise_type_id', $row->exercise_type_id)
                ->update(['points' => $row->points]);
        }

        // Sanity check, not a hard failure: flag any child whose per-exercise
        // points don't add back up to their total (e.g. from an earlier manual
        // database edit), so it can be looked at without blocking the deploy.
        $summedByChild = DB::table('child_exercise_settings')
            ->select('child_id', DB::raw('SUM(points) as summed_points'))
            ->groupBy('child_id')
            ->pluck('summed_points', 'child_id');

        $mismatches = DB::table('children')->select('id', 'total_points')->get()
            ->reject(fn ($child) => (int) $child->total_points === (int) ($summedByChild[$child->id] ?? 0))
            ->pluck('id');

        if ($mismatches->isNotEmpty()) {
            Log::warning('Per-exercise points do not sum up to children.total_points for child IDs: '.$mismatches->implode(', '));
        }
    }

    public function down(): void
    {
        // Irreversible on purpose: there is no reliable way to tell which of
        // the points were freshly earned after this ran versus backfilled.
    }
};
