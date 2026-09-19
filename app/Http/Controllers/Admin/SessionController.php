<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PracticeSession;
use App\Services\ExerciseTypes\ExerciseTypeRegistry;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SessionController extends Controller
{
    public function index(Request $request): View
    {
        $sessions = PracticeSession::query()
            ->with(['child.family', 'exerciseType'])
            ->when($request->filled('child'), fn ($query) => $query->where('child_id', $request->integer('child')))
            ->latest('started_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.sessions.index', ['sessions' => $sessions]);
    }

    public function show(PracticeSession $session, ExerciseTypeRegistry $registry): View
    {
        $session->load(['child.family', 'exerciseType', 'attempts.fact']);

        return view('admin.sessions.show', [
            'session' => $session,
            'implementation' => $registry->get($session->exerciseType->key),
        ]);
    }
}
