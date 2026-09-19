<?php

namespace App\Services;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

/**
 * Invisible bot protection for public forms, with no third-party service:
 *
 *  - a honeypot field that people never see but bots fill in, and
 *  - a signed, encrypted timestamp issued with the form, so a submission that
 *    arrives within seconds (a script) or that was never issued by this server
 *    (a forged POST) is recognised. The timestamp cannot be faked without the
 *    app key.
 *
 * Use <x-spam-guard /> in the form and inspect() when handling it. Rate
 * limiting is left to the caller because the right limits differ per form.
 */
class SpamGuard
{
    public const HONEYPOT_FIELD = 'website';

    public const TOKEN_FIELD = 'form_token';

    /** A human needs longer than this to fill in even the shortest form. */
    private const MIN_SECONDS = 4;

    /** A form left open longer than this must be reloaded. */
    private const MAX_SECONDS = 86400;

    public function token(): string
    {
        return Crypt::encryptString((string) now()->timestamp);
    }

    /**
     * @return 'honeypot'|'invalid'|'too_fast'|'expired'|null null means it looks human
     */
    public function inspect(Request $request): ?string
    {
        if (filled($request->input(self::HONEYPOT_FIELD))) {
            return 'honeypot';
        }

        try {
            $issuedAt = (int) Crypt::decryptString((string) $request->input(self::TOKEN_FIELD));
        } catch (DecryptException) {
            return 'invalid';
        }

        $age = now()->timestamp - $issuedAt;

        return match (true) {
            $age < self::MIN_SECONDS => 'too_fast',
            $age > self::MAX_SECONDS => 'expired',
            default => null,
        };
    }
}
