@php
    $tabs = [
        ['Übersicht', route('admin.dashboard'), request()->routeIs('admin.dashboard')],
        ['Familien', route('admin.families.index'), request()->routeIs('admin.families.*')],
        ['Kinder', route('admin.children.index'), request()->routeIs('admin.children.*')],
        ['Sessions', route('admin.sessions.index'), request()->routeIs('admin.sessions.*')],
        ['Übungen', route('admin.exercises.index'), request()->routeIs('admin.exercises.*', 'admin.facts.*')],
    ];
@endphp

<h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $title }}</h2>
<nav class="mt-4 flex flex-wrap gap-x-6 gap-y-2 text-sm font-medium">
    @foreach ($tabs as [$label, $url, $active])
        <a href="{{ $url }}" class="pb-1 border-b-2 {{ $active ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
            {{ __($label) }}
        </a>
    @endforeach
</nav>
