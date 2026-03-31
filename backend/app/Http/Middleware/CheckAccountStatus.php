<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckAccountStatus
 *
 * Blocks suspended and archived users from using any authenticated route.
 * Register this middleware AFTER auth:sanctum in your route groups:
 *
 *   Route::middleware(['auth:sanctum', 'account.active'])->group(function () { ... });
 *
 * Also add it to the global login flow in AuthController after credentials are
 * verified and before the token is issued.
 */
class CheckAccountStatus
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        if ($user->account_status === 'suspended') {
            // Revoke this specific token so the client cannot keep reusing it
            $request->user()->currentAccessToken()?->delete();

            return response()->json([
                'message' => 'Your account has been suspended. Please contact your administrator.',
                'code'    => 'ACCOUNT_SUSPENDED',
            ], 403);
        }

        if ($user->account_status === 'archived') {
            $request->user()->currentAccessToken()?->delete();

            return response()->json([
                'message' => 'Your account has been archived. Please contact your administrator.',
                'code'    => 'ACCOUNT_ARCHIVED',
            ], 403);
        }

        return $next($request);
    }
}