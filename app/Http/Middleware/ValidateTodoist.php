<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ValidateTodoist
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $guard = null)
    {
        if ($request->header('User-Agent') !== 'Todoist-Webhooks') {
            return response('Unauthorized.', 401);
        }

        if ($request->header('X-Todoist-Hmac-SHA256') ===
            base64_encode(hash_hmac('sha256', json_encode($request->all()), env('TODOIST_CLIENT_SECRET'), true))) {
            return response('Unauthorized.', 401);
        }

        return $next($request);
    }
}
