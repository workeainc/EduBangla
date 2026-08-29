<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireTeacher
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless($request->user()?->hasRole('Teacher'), 403);

        return $next($request);
    }
}
