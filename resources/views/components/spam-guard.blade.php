{{-- Honeypot + signed timestamp for App\Services\SpamGuard. Invisible to people. --}}
<div aria-hidden="true" style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden">
    <label for="{{ \App\Services\SpamGuard::HONEYPOT_FIELD }}">Bitte leer lassen</label>
    <input type="text" id="{{ \App\Services\SpamGuard::HONEYPOT_FIELD }}" name="{{ \App\Services\SpamGuard::HONEYPOT_FIELD }}" value="" tabindex="-1" autocomplete="off">
</div>
<input type="hidden" name="{{ \App\Services\SpamGuard::TOKEN_FIELD }}" value="{{ app(\App\Services\SpamGuard::class)->token() }}">
