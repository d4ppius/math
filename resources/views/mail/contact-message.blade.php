{{-- Plain text mail: user input is intentionally not HTML-escaped. --}}
Neue Nachricht über das Kontaktformular

Von:   {!! $contactMessage->name !!} <{!! $contactMessage->email !!}>
Thema: {{ $contactMessage->topicLabel() }}
Zeit:  {{ $contactMessage->created_at->format('d.m.Y H:i') }}

{!! $contactMessage->message !!}

--
Im Admin-Bereich ansehen: {{ route('admin.messages.show', $contactMessage) }}
Antworten Sie direkt auf diese E-Mail, sie geht an den Absender.
