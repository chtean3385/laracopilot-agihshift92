<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\PermissionService;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $permission): mixed
    {
        if (!session('crm_logged_in')) {
            return redirect()->route('login');
        }

        if (!PermissionService::check($permission)) {
            abort(403);
        }

        return $next($request);
    }
}
