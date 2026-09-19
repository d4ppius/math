<?php

namespace App\Http\Controllers;

use App\Models\Child;
use Illuminate\Http\JsonResponse;

class ChildManifestController extends Controller
{
    /**
     * A per-child Web App Manifest whose start_url is the child's own
     * magic link. iOS Safari's "Add to Home Screen" resolves the shortcut
     * via this start_url rather than the page that was open, so this is
     * what keeps the bookmark pointing at /k/{token} instead of "/" —
     * and it's also what makes the installed icon eligible for Web Push.
     */
    public function show(string $token): JsonResponse
    {
        $tokenHash = hash('sha256', $token);

        $child = Child::where('login_token_hash', $tokenHash)->where('active', true)->firstOr(function () {
            abort(404);
        });

        return response()->json([
            'name' => $child->name,
            'short_name' => $child->name,
            'start_url' => route('child.magic-link', ['token' => $token]),
            'scope' => '/',
            'display' => 'standalone',
            'background_color' => '#fff7ed',
            'theme_color' => '#fb923c',
            'orientation' => 'portrait',
            'lang' => 'de',
            'icons' => [
                [
                    'src' => $child->iconUrl(192),
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => $child->iconUrl(512),
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => $child->iconUrl(192),
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'maskable',
                ],
                [
                    'src' => $child->iconUrl(512),
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'maskable',
                ],
            ],
        ])->header('Content-Type', 'application/manifest+json');
    }
}
