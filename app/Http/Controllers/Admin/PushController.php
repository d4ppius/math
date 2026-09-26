<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Child;
use App\Models\User;
use App\Notifications\AdminBroadcast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class PushController extends Controller
{
    public function edit(): View
    {
        return view('admin.push.edit', [
            'parents' => User::with('family')->orderBy('name')->get(),
            'children' => Child::with('family')->orderBy('name')->get(),
            'counts' => [
                'parents' => User::count(),
                'parentsSubscribed' => User::whereHas('pushSubscriptions')->count(),
                'children' => Child::count(),
                'childrenSubscribed' => Child::whereHas('pushSubscriptions')->count(),
            ],
        ]);
    }

    public function send(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'audience' => ['required', 'in:all_parents,all_children,specific'],
            'recipient' => ['required_if:audience,specific', 'nullable', 'string'],
            'title' => ['required', 'string', 'max:100'],
            'body' => ['required', 'string', 'max:500'],
        ]);

        [$recipients, $targeted, $label] = match ($validated['audience']) {
            'all_parents' => [User::whereHas('pushSubscriptions')->get(), User::count(), 'Eltern'],
            'all_children' => [Child::whereHas('pushSubscriptions')->get(), Child::count(), 'Kindern'],
            'specific' => [$this->resolveRecipient($validated['recipient']), 1, 'Person'],
        };

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new AdminBroadcast($validated['title'], $validated['body']));
        }

        return redirect()->route('admin.push.edit')
            ->with('status', "An {$recipients->count()} von {$targeted} {$label} gesendet.");
    }

    /** @return Collection<int, User|Child> */
    private function resolveRecipient(string $recipient): Collection
    {
        [$type, $id] = explode(':', $recipient, 2) + [null, null];

        $model = match ($type) {
            'user' => User::find($id),
            'child' => Child::find($id),
            default => null,
        };

        return $model && $model->pushSubscriptions()->exists() ? collect([$model]) : collect();
    }
}
