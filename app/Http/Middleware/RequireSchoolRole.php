<?php

namespace App\Http\Middleware;

use App\Models\School;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireSchoolRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();
        $school = $request->route('school');
        $schoolId = $school instanceof School ? $school->id : (int) $school;
        if (! $user || ! $schoolId || ! $user->schoolMemberships()->where(['school_id' => $schoolId, 'role' => $role, 'status' => 'active'])->exists()) {
            abort(403);
        }

        return $next($request);
    }
}
