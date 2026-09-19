{{--
    An e-mail address that address harvesters won't find in the markup: without
    JavaScript it reads "name [at] example [dot] ch"; with JavaScript it becomes
    a normal mailto link. The address is never written out as one string.
--}}
@props(['email'])

@php [$user, $domain] = array_pad(explode('@', (string) $email, 2), 2, ''); @endphp

<span x-data="{ user: @js($user), domain: @js($domain), ready: false }" x-init="ready = true">
    <a x-cloak x-show="ready" :href="'mailto:' + user + '@' + domain" x-text="user + '@' + domain" {{ $attributes->merge(['class' => 'underline']) }}></a>
    <span x-show="!ready">{{ $user }} [at] {{ str_replace('.', ' [dot] ', $domain) }}</span>
</span>
