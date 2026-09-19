<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        // 404 instead of 403 so the admin area doesn't advertise its existence.
        abort_unless($request->user()?->is_admin, 404);

        return $next($request);
    }
}
