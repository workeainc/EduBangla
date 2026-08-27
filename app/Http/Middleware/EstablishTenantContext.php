<?php

namespace App\Http\Middleware;

use App\Domain\School\TenantContext;
use App\Models\School;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EstablishTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $school = $request->route('school');
        if (! $school instanceof School) {
            abort(404);
        }

        app(TenantContext::class)->activate($school, $request->user());

        try {
            return $next($request);
        } finally {
            app(TenantContext::class)->clear();
        }
    }
}
