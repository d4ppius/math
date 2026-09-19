<x-app-layout>
    <x-slot name="header">@include('admin._header', ['title' => __('Session-Details')])</x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('admin._flash')

            <a href="{{ route('admin.sessions.index') }}" class="text-sm text-indigo-600 underline">← {{ __('Alle Sessions') }}</a>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <dl class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm">
                    <div><dt class="text-gray-500">{{ __('Kind') }}</dt><dd class="font-medium">{{ $session->child->name }}</dd></div>
                    <div><dt class="text-gray-500">{{ __('Familie') }}</dt><dd class="font-medium"><a href="{{ route('admin.families.show', $session->child->family_id) }}" class="text-indigo-600 hover:underline">{{ $session->child->family->name }}</a></dd></div>
                    <div><dt class="text-gray-500">{{ __('Übung') }}</dt><dd class="font-medium">{{ $session->exerciseType->name }}</dd></div>
                    <div><dt class="text-gray-500">{{ __('Start') }}</dt><dd class="font-medium">{{ $session->started_at->format('d.m.Y H:i') }}</dd></div>
                    <div><dt class="text-gray-500">{{ __('Geplante Dauer') }}</dt><dd class="font-medium">{{ intdiv($session->planned_duration_seconds, 60) }} {{ __('Minuten') }}</dd></div>
                    <div><dt class="text-gray-500">{{ __('Status') }}</dt><dd class="font-medium">{{ $session->status }}</dd></div>
                    <div><dt class="text-gray-500">{{ __('Richtig') }}</dt><dd class="font-medium">{{ $session->questions_correct }}/{{ $session->questions_answered }}</dd></div>
                    <div><dt class="text-gray-500">{{ __('Punkte') }}</dt><dd class="font-medium">{{ $session->total_points }}</dd></div>
                </dl>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Antworten') }}</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 border-b">
                                <th class="py-2 pe-4 font-medium">{{ __('Aufgabe') }}</th>
                                <th class="py-2 pe-4 font-medium">{{ __('Antwort') }}</th>
                                <th class="py-2 pe-4 font-medium">{{ __('Richtig') }}</th>
                                <th class="py-2 pe-4 font-medium">{{ __('Zeit') }}</th>
                                <th class="py-2 font-medium">{{ __('Punkte') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($session->attempts as $attempt)
                                <tr class="border-b last:border-0">
                                    <td class="py-2 pe-4">{{ $implementation->formatPrompt($attempt->fact) }}</td>
                                    <td class="py-2 pe-4">{{ $attempt->given_answer ?? '–' }}</td>
                                    <td class="py-2 pe-4">{{ $attempt->is_correct ? '✓' : '✗' }}</td>
                                    <td class="py-2 pe-4">{{ number_format($attempt->response_time_ms / 1000, 1) }} s</td>
                                    <td class="py-2">{{ $attempt->points_awarded }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-4 text-gray-500">{{ __('Keine Antworten.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
