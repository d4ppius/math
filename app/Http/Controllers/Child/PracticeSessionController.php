<?php

namespace App\Http\Controllers\Child;

use App\Events\PracticeSessionCompleted;
use App\Http\Controllers\ChildPreviewController;
use App\Http\Controllers\Controller;
use App\Models\PracticeSession;
use App\Services\AdaptiveSelection\AttemptRecorder;
use App\Services\AdaptiveSelection\WeightedFactSelector;
use App\Services\ExerciseTypes\ExerciseTypeRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PracticeSessionController extends Controller
{
    public function start(Request $request): RedirectResponse
    {
        $child = $request->user('child');

        $request->validate(['exercise' => ['nullable', 'string', 'max:50']]);

        $enabled = $child->exerciseSettings()->where('enabled', true)->with('exerciseType')->orderBy('exercise_type_id')->get();
        $available = $child->availableExercises();

        if ($request->filled('exercise')) {
            // The child chose one; the server checks it, not just the buttons shown.
            if (! $enabled->contains(fn ($setting) => $setting->exerciseType->key === $request->input('exercise'))) {
                return $this->backHome('Diese Übung ist nicht freigeschaltet. Frag deine Eltern!');
            }

            $chosen = $available->first(fn ($setting) => $setting->exerciseType->key === $request->input('exercise'));
        } elseif ($available->count() === 1) {
            $chosen = $available->first();
        } elseif ($available->count() > 1) {
            return $this->backHome('Wähle aus, was du üben möchtest.');
        } else {
            return $this->backHome($enabled->isEmpty()
                ? 'Die Übung ist noch nicht eingerichtet. Frag deine Eltern!'
                : 'Diese Übung ist gerade nicht verfügbar.');
        }

        if (! $chosen) {
            return $this->backHome('Diese Übung ist gerade nicht verfügbar.');
        }

        $isPreview = $request->session()->has(ChildPreviewController::SESSION_KEY);

        // A preview never resumes a real session: that would let a "harmless"
        // preview keep writing into the child's own, actually-in-progress one.
        if (! $isPreview && $existing = $child->resumableSessionFor($chosen->exercise_type_id)) {
            return redirect()->route('child.sessions.show', $existing);
        }

        $session = PracticeSession::create([
            'child_id' => $child->id,
            'exercise_type_id' => $chosen->exercise_type_id,
            'started_at' => now(),
            'planned_duration_seconds' => $chosen->session_duration_minutes * 60,
            'status' => 'active',
            'is_preview' => $isPreview,
        ]);

        return redirect()->route('child.sessions.show', $session);
    }

    private function backHome(string $message): RedirectResponse
    {
        return redirect()->route('child.home')->withErrors(['exercise' => $message]);
    }

    public function show(PracticeSession $session): View
    {
        $settings = $session->child->exerciseSettings()
            ->where('exercise_type_id', $session->exercise_type_id)
            ->first();

        return view('child.practice', [
            'session' => $session,
            'child' => $session->child,
            // The countdown always runs; this only controls whether it is shown.
            'showTimer' => $settings?->show_timer ?? true,
            'soundEnabled' => $settings?->sound_enabled ?? true,
            'showHints' => $settings?->show_hints ?? false,
        ]);
    }

    public function nextQuestion(PracticeSession $session, WeightedFactSelector $selector, ExerciseTypeRegistry $registry): JsonResponse
    {
        if (! $session->isActive() || ! $session->hasTimeRemaining()) {
            $this->completeSession($session);

            return response()->json(['session_over' => true]);
        }

        $settings = $session->child->exerciseSettings()
            ->where('exercise_type_id', $session->exercise_type_id)
            ->first();

        $fact = $selector->next($session->child, $settings, $session);

        if (! $fact) {
            return response()->json(['session_over' => true]);
        }

        $session->update([
            'current_fact_id' => $fact->id,
            'current_question_issued_at' => now(),
        ]);

        $implementation = $registry->get($session->exerciseType->key);

        return response()->json([
            'session_over' => false,
            'prompt' => $implementation->formatPrompt($fact),
            'time_remaining_seconds' => $this->timeRemainingSeconds($session),
        ]);
    }

    public function attempt(Request $request, PracticeSession $session, AttemptRecorder $recorder): JsonResponse
    {
        $request->validate(['answer' => ['required', 'integer']]);

        if (! $session->isActive() || ! $session->current_fact_id) {
            return response()->json(['error' => 'no_open_question'], 422);
        }

        if (! $session->hasTimeRemaining()) {
            $this->completeSession($session);

            return response()->json(['session_over' => true]);
        }

        $result = $recorder->record($session, (int) $request->input('answer'));

        $session->refresh();
        $sessionOver = ! $session->hasTimeRemaining();

        if ($sessionOver) {
            $this->completeSession($session);
        }

        return response()->json(array_merge($result, [
            'session_over' => $sessionOver,
            'time_remaining_seconds' => $this->timeRemainingSeconds($session),
        ]));
    }

    public function finish(PracticeSession $session): RedirectResponse
    {
        $this->completeSession($session);

        return redirect()->route('child.sessions.summary', $session);
    }

    public function summary(PracticeSession $session): View
    {
        return view('child.summary', [
            'session' => $session,
            'child' => $session->child,
            'newBadges' => $session->child->badgesEarnedDuring($session),
        ]);
    }

    private function completeSession(PracticeSession $session): void
    {
        if ($session->status !== 'active') {
            return;
        }

        $session->update([
            'status' => 'completed',
            'ended_at' => now(),
            'current_fact_id' => null,
            'current_question_issued_at' => null,
        ]);

        // A preview's completion must stay invisible to badges and the daily
        // goal (EvaluateBadges, EvaluateDailyGoal) — skipping the event they
        // both listen for is simpler and more certain than teaching each of
        // them about is_preview individually.
        if (! $session->is_preview) {
            PracticeSessionCompleted::dispatch($session);
        }
    }

    private function timeRemainingSeconds(PracticeSession $session): int
    {
        // Signed diff: negative when started_at is somehow in the future
        // (clock/timezone skew) rather than the past. Clamping elapsed to
        // 0 in that case avoids diffInSeconds()'s sign flipping "elapsed"
        // into a huge bogus remaining-time value.
        $elapsedSeconds = max(0, (int) round($session->started_at->diffInSeconds(now(), false)));

        return max(0, $session->planned_duration_seconds - $elapsedSeconds);
    }
}
