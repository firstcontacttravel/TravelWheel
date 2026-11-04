<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Pages for a company's Admins. Runs after EnsureCompanyUser. */
class EnsureCompanyAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->attributes->get('companyUser')?->isCompanyAdmin(), 403);

        return $next($request);
    }
}
