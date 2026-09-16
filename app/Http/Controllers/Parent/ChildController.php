<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\Child;
use App\Models\ExerciseType;
use App\Services\ExerciseTypes\ExerciseTypeRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChildController extends Controller
{
    public const AVATARS = ['fox', 'owl', 'cat', 'bear', 'rabbit', 'panda'];

    public const COLOR_THEMES = ['orange', 'blue', 'green', 'pink', 'purple'];

    public function create(): View
    {
        return view('parent.children.create', [
            'avatars' => self::AVATARS,
            'colorThemes' => self::COLOR_THEMES,
        ]);
    }

    public function store(Request $request, ExerciseTypeRegistry $registry): RedirectResponse
    {
        $validated = $this->validateChild($request);

        $child = new Child([
            'family_id' => $request->user()->family_id,
            'name' => $validated['name'],
            'avatar' => $validated['avatar'],
            'color_theme' => $validated['color_theme'],
        ]);
        // login_token_hash is required (unique, not-null) before the first save.
        $child->login_token_hash = '';
        $child->save();

        $plainToken = $child->generateLoginToken();

        if (! empty($validated['pin'])) {
            $child->setPin($validated['pin']);
        }

        $this->createDefaultExerciseSettings($child, $registry);

        return redirect()->route('parent.children.edit', $child)
            ->with('plain_login_token', $plainToken)
            ->with('status', 'Kind wurde angelegt.');
    }

    public function edit(Request $request, Child $child): View
    {
        $this->authorize('view', $child);

        return view('parent.children.edit', [
            'child' => $child,
            'avatars' => self::AVATARS,
            'colorThemes' => self::COLOR_THEMES,
            'plainLoginToken' => session('plain_login_token'),
            'magicLinkUrl' => session('plain_login_token')
                ? route('child.magic-link', ['token' => session('plain_login_token')])
                : null,
        ]);
    }

    public function update(Request $request, Child $child): RedirectResponse
    {
        $this->authorize('update', $child);

        $validated = $this->validateChild($request, $child);

        $child->update([
            'name' => $validated['name'],
            'avatar' => $validated['avatar'],
            'color_theme' => $validated['color_theme'],
            'active' => $request->boolean('active', true),
        ]);

        if ($request->filled('pin')) {
            $child->setPin($validated['pin']);
        } elseif ($request->boolean('remove_pin')) {
            $child->setPin(null);
        }

        return redirect()->route('parent.children.edit', $child)
            ->with('status', 'Einstellungen gespeichert.');
    }

    public function destroy(Request $request, Child $child): RedirectResponse
    {
        $this->authorize('delete', $child);

        $child->delete();

        return redirect()->route('dashboard')->with('status', 'Kind wurde entfernt.');
    }

    public function regenerateToken(Request $request, Child $child): RedirectResponse
    {
        $this->authorize('update', $child);

        $plainToken = $child->generateLoginToken();

        return redirect()->route('parent.children.edit', $child)
            ->with('plain_login_token', $plainToken)
            ->with('status', 'Neuer Link wurde erzeugt. Der alte Homescreen-Icon funktioniert nicht mehr.');
    }

    private function createDefaultExerciseSettings(Child $child, ExerciseTypeRegistry $registry): void
    {
        foreach ($registry->all() as $implementation) {
            $exerciseType = ExerciseType::where('key', $implementation->key())->first();

            if (! $exerciseType) {
                continue;
            }

            $child->exerciseSettings()->create([
                'exercise_type_id' => $exerciseType->id,
                'active_groups' => $implementation->defaultActiveGroups(),
                'session_duration_minutes' => 10,
                'target_frequency' => 'daily',
                'sound_enabled' => true,
            ]);
        }
    }

    private function validateChild(Request $request, ?Child $child = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'avatar' => ['required', 'string', 'in:'.implode(',', self::AVATARS)],
            'color_theme' => ['required', 'string', 'in:'.implode(',', self::COLOR_THEMES)],
            'pin' => ['nullable', 'digits:4'],
        ]);
    }
}
