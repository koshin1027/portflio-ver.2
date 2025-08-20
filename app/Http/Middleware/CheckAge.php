<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckAge
{
    public function handle(Request $request, Closure $next, $age = 18)
    {
        if ($request->input('age', 0) < $age) {
            abort(403, '年齢が制限に満たないためアクセスできません');
        }

        return $next($request);
    }
}