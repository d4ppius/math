<?php

namespace App\Http\Middleware;

use App\Models\Child;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Guards child-guard routes so a logged-in child can only touch its own
 * resources, even if it guesses another child's route-model-bound id.
 */
class EnsureChildOwnsResource
{
    public function handle(Request $request, Closure $next): Response
    {
        $childId = Auth::guard('child')->id();

        foreach ($request->route()->parameters() as $parameter) {
            if ($parameter instanceof Child && $parameter->id !== $childId) {
                throw new HttpException(403, 'Nicht dein Bereich.');
            }

            if (is_object($parameter) && isset($parameter->child_id) && $parameter->child_id !== $childId) {
                throw new HttpException(403, 'Nicht dein Bereich.');
            }
        }

        return $next($request);
    }
}
