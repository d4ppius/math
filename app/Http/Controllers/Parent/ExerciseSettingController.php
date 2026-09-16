<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\Child;
use App\Services\ExerciseTypes\ExerciseTypeRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExerciseSettingController extends Controller
{
    public function edit(Request $request, Child $child, ExerciseTypeRegistry $registry): View
    {
        $this->authorize('view', $child);

        $settings = $child->exerciseSettings()->with('exerciseType')->get()
            ->map(function ($setting) use ($registry) {
                $setting->implementation = $registry->get($setting->exerciseType->key);

                return $setting;
            });

        return view('parent.children.exercise-settings', [
            'child' => $child,
            'settings' => $settings,
        ]);
    }

    public function update(Request $request, Child $child): RedirectResponse
    {
        $this->authorize('update', $child);

        $validated = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*.id' => ['required', 'integer'],
            'settings.*.active_groups' => ['array'],
            'settings.*.active_groups.*' => ['integer'],
            'settings.*.session_duration_minutes' => ['required', 'integer', 'min:3', 'max:30'],
            'settings.*.target_frequency' => ['required', 'in:daily,weekdays,custom'],
        ]);

        foreach ($validated['settings'] as $settingInput) {
            $setting = $child->exerciseSettings()->findOrFail($settingInput['id']);

            $setting->update([
                'active_groups' => $settingInput['active_groups'] ?? [],
                'session_duration_minutes' => $settingInput['session_duration_minutes'],
                'target_frequency' => $settingInput['target_frequency'],
            ]);
        }

        return redirect()->route('parent.children.exercise-settings.edit', $child)
            ->with('status', 'Übungseinstellungen gespeichert.');
    }
}
