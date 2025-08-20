<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle($request, Closure $next, ...$roles)
    {
        $user = Auth::user();
        if (!$user || !in_array($user->role, $roles)) {
            abort(403, 'この画面へのアクセス権限がありません');
        }
        return $next($request);
    }
}
