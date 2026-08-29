<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireStudent
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $school = $request->route('school');
        if (! $user || ! $school || ! $user->schoolMemberships()->where(['school_id' => $school->id, 'role' => 'student', 'status' => 'active'])->exists() || ! $school->students()->where('user_id', $user->id)->where('status', 'active')->exists()) {
            abort(403);
        }

return $next($request);
    }
}
