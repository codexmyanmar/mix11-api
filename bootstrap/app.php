<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\Rich88IpWhitelist;
use App\Console\Commands\SyncBoleGameLogs;  // Add this

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        SyncBoleGameLogs::class,  // Register command here
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'rich88.ip' => Rich88IpWhitelist::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();