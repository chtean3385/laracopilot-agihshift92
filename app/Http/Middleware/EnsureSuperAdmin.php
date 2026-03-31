<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (!session('crm_logged_in')) {
            return redirect()->route('login');
        }

        if (session('crm_user_role') !== 'Super Admin') {
            abort(403, 'Access restricted to Platform Administrators only.');
        }

        return $next($request);
    }
}
