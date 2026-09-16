<?php

namespace App\Http\Controllers\Child;

use App\Events\PracticeSessionCompleted;
use App\Http\Controllers\Controller;
use App\Models\ExerciseType;
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

        $exerciseType = ExerciseType::where('key', 'multiplication')->firstOrFail();

        $existing = $child->practiceSessions()
            ->where('exercise_type_id', $exerciseType->id)
            ->where('status', 'active')
            ->latest('id')
            ->first();

        if ($existing && $existing->hasTimeRemaining()) {
            return redirect()->route('child.sessions.show', $existing);
        }

        $settings = $child->exerciseSettings()->where('exercise_type_id', $exerciseType->id)->first();

        if (! $settings) {
            return redirect()->route('child.home')
                ->withErrors(['exercise' => 'Die Übung ist noch nicht eingerichtet. Frag deine Eltern!']);
        }

        $session = PracticeSession::create([
            'child_id' => $child->id,
            'exercise_type_id' => $exerciseType->id,
            'started_at' => now(),
            'planned_duration_seconds' => $settings->session_duration_minutes * 60,
            'status' => 'active',
        ]);

        return redirect()->route('child.sessions.show', $session);
    }

    public function show(PracticeSession $session): View
    {
        return view('child.practice', [
            'session' => $session,
            'child' => $session->child,
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

        PracticeSessionCompleted::dispatch($session);
    }

    private function timeRemainingSeconds(PracticeSession $session): int
    {
        return max(0, $session->planned_duration_seconds - $session->started_at->diffInSeconds(now()));
    }
}
