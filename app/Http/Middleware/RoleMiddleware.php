<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        if (! $request->user()) return redirect()->route('login');
        if (! in_array($request->user()->role, $roles)) abort(403, 'You do not have access to this page.');
        return $next($request);
    }
}
