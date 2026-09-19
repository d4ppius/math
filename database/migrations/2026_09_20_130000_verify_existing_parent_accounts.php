<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * E-mail verification is now required to use the parent area. Parents who
 * registered before that would otherwise be locked out until they verify, so
 * the existing accounts count as verified.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->whereNull('email_verified_at')->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        // Irreversible on purpose: we no longer know which accounts were unverified.
    }
};
