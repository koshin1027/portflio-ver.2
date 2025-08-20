<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{

    protected $middleware = [
        // ...existing code...
    ];

    protected $middlewareGroups = [
        'web' => [
            // ...existing code...
        ],
        'api' => [
            // ...existing code...
        ],
    ];

    protected $routeMiddleware = [
        'role' => \App\Http\Middleware\RoleMiddleware::class,
        'check.age' => \App\Http\Middleware\CheckAge::class,
    ];
}
