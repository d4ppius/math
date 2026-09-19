<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExerciseType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExerciseTypeController extends Controller
{
    public function index(): View
    {
        return view('admin.exercises.index', [
            'exerciseTypes' => ExerciseType::withCount(['facts', 'childExerciseSettings'])->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, ExerciseType $exerciseType): RedirectResponse
    {
        $validated = $request->validate(['is_active' => ['required', 'boolean']]);

        $exerciseType->update(['is_active' => $validated['is_active']]);

        return redirect()->route('admin.exercises.index')
            ->with('status', $exerciseType->is_active ? 'Übung aktiviert.' : 'Übung deaktiviert.');
    }
}
