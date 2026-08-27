<?php

namespace App\Http\Middleware;

use App\Models\School;
use App\Models\SchoolUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireSchoolAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $school = $request->route('school');
        $id = $school instanceof School ? $school->id : (int) $school;
        abort_unless(SchoolUser::where(['school_id' => $id, 'user_id' => $request->user()->id, 'role' => 'school-admin', 'status' => 'active'])->exists(), 403);

        return $next($request);
    }
}
