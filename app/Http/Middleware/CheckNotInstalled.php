<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckNotInstalled
{
    public function handle(Request $request, Closure $next)
    {
        if (file_exists(storage_path('installed.lock'))) {
            return redirect('/login');
        }

        return $next($request);
    }
}
