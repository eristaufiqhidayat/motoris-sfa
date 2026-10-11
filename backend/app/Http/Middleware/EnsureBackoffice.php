<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Web ERP hanya untuk admin depo dan staf gudang. */
class EnsureBackoffice
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless($request->user()?->isAdmin(), 403, 'Halaman ini untuk admin depo dan staf gudang.');

        return $next($request);
    }
}
