<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the CAS webhook with the shared bearer token from
 * CAS_GENESIS_WORLD_WEBHOOK_BEARER. An unset token rejects everything
 * (503) instead of letting an empty comparison through.
 */
class EnsureValidCasWebhookToken
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('services.cas_genesis_world.webhook_bearer');

        if ($expected === '') {
            return response()->json(['message' => 'Webhook is not configured.'], 503);
        }

        if (! hash_equals($expected, (string) $request->bearerToken())) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        return $next($request);
    }
}
