<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireSchoolRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();
        $school = $request->route('school');
        if (! $user || ! $school || ! $user->schoolMemberships()->where(['school_id' => $school->id, 'role' => $role, 'status' => 'active'])->exists()) {
            abort(403);
        }

        return $next($request);
    }
}
