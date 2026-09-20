<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\Child;
use App\Services\ExerciseTypes\ExerciseSettingsProvisioner;
use App\Services\ExerciseTypes\ExerciseTypeRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ExerciseSettingController extends Controller
{
    public function edit(Request $request, Child $child, ExerciseTypeRegistry $registry, ExerciseSettingsProvisioner $provisioner): View
    {
        $this->authorize('view', $child);

        // Self-heal children that predate an exercise type's seed data
        // (e.g. deployed before `db:seed` was run) instead of showing a
        // silently empty settings page.
        $provisioner->ensureDefaultsFor($child);

        $settings = $child->exerciseSettings()->with('exerciseType')->orderBy('exercise_type_id')->get()
            ->map(function ($setting) use ($registry) {
                $setting->implementation = $registry->get($setting->exerciseType->key);

                return $setting;
            });

        return view('parent.children.exercise-settings', [
            'child' => $child,
            'settings' => $settings,
        ]);
    }

    public function update(Request $request, Child $child, ExerciseTypeRegistry $registry): RedirectResponse
    {
        $this->authorize('update', $child);

        $validated = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*.id' => ['required', 'integer'],
            'settings.*.active_groups' => ['array'],
            'settings.*.active_groups.*' => ['integer'],
            'settings.*.session_duration_minutes' => ['required', 'integer', 'min:3', 'max:30'],
            'settings.*.target_frequency' => ['required', 'in:daily,weekdays,custom'],
            'settings.*.enabled' => ['required', 'boolean'],
            'settings.*.show_timer' => ['required', 'boolean'],
            'settings.*.speed_bonus_enabled' => ['required', 'boolean'],
            'settings.*.sound_enabled' => ['required', 'boolean'],
        ]);

        $settings = $child->exerciseSettings()->with('exerciseType')->get()->keyBy('id');
        $errors = [];

        foreach ($validated['settings'] as $index => $settingInput) {
            $setting = $settings->get((int) $settingInput['id']) ?? abort(404);
            $groups = $registry->get($setting->exerciseType->key);
            $submitted = $settingInput['active_groups'] ?? [];

            if (array_diff($submitted, array_keys($groups->groups())) !== []) {
                $errors["settings.{$index}.active_groups"] = 'Bitte wähle nur Angebotenes aus der Liste.';
            } elseif ($settingInput['enabled'] && $submitted === []) {
                $errors["settings.{$index}.active_groups"] = "Bitte wähle für «{$groups->label()}» mindestens eine Auswahl bei {$groups->groupsLabel()}.";
            }
        }

        if (collect($validated['settings'])->where('enabled', true)->isEmpty()) {
            $errors['settings'] = 'Mindestens eine Übung muss freigeschaltet bleiben, sonst hat das Kind nichts zum Üben.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        foreach ($validated['settings'] as $settingInput) {
            $setting = $settings->get((int) $settingInput['id']);

            $setting->update([
                'enabled' => (bool) $settingInput['enabled'],
                'active_groups' => $settingInput['active_groups'] ?? [],
                'session_duration_minutes' => $settingInput['session_duration_minutes'],
                'target_frequency' => $settingInput['target_frequency'],
                'show_timer' => (bool) $settingInput['show_timer'],
                'speed_bonus_enabled' => (bool) $settingInput['speed_bonus_enabled'],
                'sound_enabled' => (bool) $settingInput['sound_enabled'],
            ]);
        }

        return redirect()->route('parent.children.exercise-settings.edit', $child)
            ->with('status', 'Übungseinstellungen gespeichert.');
    }
}
