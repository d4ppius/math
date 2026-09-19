<?php

return [
    // Where messages from the public contact form are e-mailed. If unset the
    // messages are still stored (and visible in the admin area), just not mailed.
    'recipient' => env('CONTACT_MAIL_TO'),

    'topics' => [
        'question' => 'Frage zur App',
        'support' => 'Hilfe bei einem Problem',
        'feedback' => 'Rückmeldung oder Idee',
        'other' => 'Etwas anderes',
    ],

    // Handled messages are deleted after this many months (see contact:prune
    // and the privacy statement).
    'retention_months' => 12,
];
