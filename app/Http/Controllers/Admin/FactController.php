<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExerciseType;
use App\Models\Fact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FactController extends Controller
{
    public function index(Request $request, ExerciseType $exerciseType): View
    {
        $facts = $exerciseType->facts()
            ->when($request->filled('group'), fn ($query) => $query->where('difficulty_group', $request->integer('group')))
            ->orderBy('difficulty_group')
            ->orderBy('operand_a')
            ->orderBy('operand_b')
            ->paginate(50)
            ->withQueryString();

        return view('admin.facts.index', [
            'exerciseType' => $exerciseType,
            'facts' => $facts,
            'groups' => $exerciseType->facts()->distinct()->orderBy('difficulty_group')->pluck('difficulty_group'),
        ]);
    }

    public function edit(Fact $fact): View
    {
        return view('admin.facts.edit', ['fact' => $fact->load('exerciseType')]);
    }

    public function update(Request $request, Fact $fact): RedirectResponse
    {
        $validated = $request->validate([
            'operand_a' => [
                'required', 'integer', 'between:0,255',
                Rule::unique('facts')
                    ->where('exercise_type_id', $fact->exercise_type_id)
                    ->where('operand_b', $request->input('operand_b'))
                    ->ignore($fact->id),
            ],
            'operand_b' => ['required', 'integer', 'between:0,255'],
            'correct_answer' => ['required', 'integer'],
            'difficulty_group' => ['required', 'integer', 'between:0,255'],
        ], [
            'operand_a.unique' => 'Diese Aufgabe existiert bereits.',
        ]);

        $fact->update($validated);

        return redirect()->route('admin.exercises.facts.index', $fact->exercise_type_id)
            ->with('status', 'Aufgabe gespeichert.');
    }
}
