@if (session('status'))
    <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-lg p-4">{{ session('status') }}</div>
@endif
@if (session('error'))
    <div class="bg-red-50 border border-red-200 text-red-800 text-sm rounded-lg p-4">{{ session('error') }}</div>
@endif
