<div class="overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead>
            <tr class="text-left text-gray-500 border-b">
                <th class="py-2 pe-4 font-medium">{{ __('Start') }}</th>
                <th class="py-2 pe-4 font-medium">{{ __('Kind') }}</th>
                <th class="py-2 pe-4 font-medium">{{ __('Familie') }}</th>
                <th class="py-2 pe-4 font-medium">{{ __('Übung') }}</th>
                <th class="py-2 pe-4 font-medium">{{ __('Status') }}</th>
                <th class="py-2 pe-4 font-medium">{{ __('Richtig') }}</th>
                <th class="py-2 pe-4 font-medium">{{ __('Punkte') }}</th>
                <th class="py-2"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($sessions as $session)
                <tr class="border-b last:border-0">
                    <td class="py-2 pe-4 whitespace-nowrap">{{ $session->started_at->format('d.m.Y H:i') }}</td>
                    <td class="py-2 pe-4">{{ $session->child->name }}</td>
                    <td class="py-2 pe-4">
                        <a href="{{ route('admin.families.show', $session->child->family_id) }}" class="text-indigo-600 hover:underline">{{ $session->child->family->name }}</a>
                    </td>
                    <td class="py-2 pe-4">
                        {{ $session->exerciseType->name }}
                        @if ($session->is_preview)
                            <span class="ms-1 text-xs bg-amber-100 text-amber-800 rounded px-1.5 py-0.5">{{ __('Vorschau') }}</span>
                        @endif
                    </td>
                    <td class="py-2 pe-4">{{ $session->status === 'completed' ? __('Abgeschlossen') : ($session->status === 'active' ? __('Läuft') : $session->status) }}</td>
                    <td class="py-2 pe-4">{{ $session->questions_correct }}/{{ $session->questions_answered }}</td>
                    <td class="py-2 pe-4">{{ $session->total_points }}</td>
                    <td class="py-2 text-end"><a href="{{ route('admin.sessions.show', $session) }}" class="text-indigo-600 hover:underline">{{ __('Details') }}</a></td>
                </tr>
            @empty
                <tr><td colspan="8" class="py-4 text-gray-500">{{ __('Noch keine Sessions.') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
