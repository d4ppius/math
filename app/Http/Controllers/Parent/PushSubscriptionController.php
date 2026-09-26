<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string'],
            'keys.p256dh' => ['required', 'string'],
            'keys.auth' => ['required', 'string'],
        ]);

        $request->user()->updatePushSubscription(
            $validated['endpoint'],
            $validated['keys']['p256dh'],
            $validated['keys']['auth'],
        );

        return response()->json(['status' => 'ok']);
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->validate(['endpoint' => ['nullable', 'string']]);

        if ($endpoint = $request->input('endpoint')) {
            $request->user()->deletePushSubscription($endpoint);
        } else {
            // This browser never subscribed itself (the database only says
            // "subscribed" because of a different device) — "Deaktivieren"
            // still has to mean deactivated, so every subscription goes.
            $request->user()->pushSubscriptions()->delete();
        }

        return response()->json(['status' => 'ok']);
    }
}
