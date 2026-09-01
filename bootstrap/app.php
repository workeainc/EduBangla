<?php

use App\Http\Middleware\EstablishTenantContext;
use App\Http\Middleware\RequireSchoolAdmin;
use App\Http\Middleware\RequireSchoolRole;
use App\Http\Middleware\RequireStudent;
use App\Http\Middleware\RequireTeacher;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tenant.context' => EstablishTenantContext::class,
            'school.admin' => RequireSchoolAdmin::class,
            'teacher' => RequireTeacher::class,
            'student' => RequireStudent::class,
            'school.role' => RequireSchoolRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
