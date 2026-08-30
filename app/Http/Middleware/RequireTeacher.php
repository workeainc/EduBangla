<?php

namespace App\Http\Middleware;

use App\Models\School;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireTeacher
{
    public function handle(Request $request, Closure $next): Response
    {
        $school = $request->route('school');
        $schoolId = $school instanceof School ? $school->id : (int) $school;
        abort_unless($schoolId && $request->user()?->schoolMemberships()->where(['school_id' => $schoolId, 'role' => 'teacher', 'status' => 'active'])->exists(), 403);

        return $next($request);
    }
}
