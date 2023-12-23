<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;


class AddRefreshTokenHeader
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {    
        if ($request->cookie('refresh_token')){
            $request->headers->set('Authorization', 'Bearer '.$request->cookie('refresh_token'));
        }
        return $next($request);
    }
}
