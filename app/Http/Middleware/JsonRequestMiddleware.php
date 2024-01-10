<?php
namespace App\Http\Middleware;

use Closure;

class JsonRequestMiddleware
{
    public function handle($request, Closure $next)
    {
        $request->headers->add(['Accept' => 'application/json']);

        return $next($request);
    }
}
