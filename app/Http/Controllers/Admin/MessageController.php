<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MessageController extends Controller
{
    public function index(Request $request): View
    {
        $filter = in_array($request->query('show'), ['open', 'handled', 'all'], true) ? $request->query('show') : 'open';

        $messages = ContactMessage::query()
            ->when($filter === 'open', fn ($query) => $query->whereNull('handled_at'))
            ->when($filter === 'handled', fn ($query) => $query->whereNotNull('handled_at'))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.messages.index', [
            'messages' => $messages,
            'filter' => $filter,
            'openCount' => ContactMessage::whereNull('handled_at')->count(),
        ]);
    }

    public function show(ContactMessage $message): View
    {
        return view('admin.messages.show', ['message' => $message]);
    }

    public function update(Request $request, ContactMessage $message): RedirectResponse
    {
        $validated = $request->validate(['handled' => ['required', 'boolean']]);

        $message->update(['handled_at' => $validated['handled'] ? now() : null]);

        return redirect()->route('admin.messages.show', $message)
            ->with('status', $message->isHandled() ? 'Als erledigt markiert.' : 'Wieder als offen markiert.');
    }

    public function destroy(ContactMessage $message): RedirectResponse
    {
        $message->delete();

        return redirect()->route('admin.messages.index')->with('status', 'Nachricht wurde gelöscht.');
    }
}
