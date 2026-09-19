<?php

namespace Tests\Concerns;

use App\Services\SpamGuard;

/** Form fields as a real visitor would send them: the form was rendered a moment ago. */
trait PassesSpamGuard
{
    protected function humanFormFields(): array
    {
        $token = app(SpamGuard::class)->token();
        $this->travel(15)->seconds();

        return [
            SpamGuard::HONEYPOT_FIELD => '',
            SpamGuard::TOKEN_FIELD => $token,
            'privacy' => '1',
        ];
    }
}
