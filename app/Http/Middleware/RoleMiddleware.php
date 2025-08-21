<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle($request, Closure $next, ...$roles)
    {
        $user = Auth::user();
        if (!in_array($user->role, $roles)) {
            abort(403, 'この画面へのアクセス権限がありません');
        }
        return $next($request);
    }
}
