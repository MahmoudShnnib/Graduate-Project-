<?php

namespace App\Http\Middleware;

use App\Services\AuditService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResetAuditBatch
{
    /**
     * Give each request its own audit batch id. Matters under Octane, where the static
     * would otherwise carry across requests and merge unrelated operations.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        AuditService::resetBatch();

        return $next($request);
    }
}
