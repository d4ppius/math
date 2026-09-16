<?php

namespace App\Http\Middleware;

use App\Services\Tenancy\FamilyContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ResolveFamilyContext
{
    public function __construct(private FamilyContext $familyContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        $family = Auth::guard('web')->user()?->family
            ?? Auth::guard('child')->user()?->family;

        if ($family) {
            $this->familyContext->set($family);
        }

        return $next($request);
    }
}
