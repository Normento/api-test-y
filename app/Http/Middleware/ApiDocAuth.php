<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiDocAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $credentials = [
            'username' => 'Yl@m!',
            'password' => 'Yl@m!2@24&'
        ];
        if ($request->getUser() != $credentials['username'] || $request->getPassword() != $credentials['password']) {
             $headers = ['WWW-Authenticate' => 'Basic'];
            return response(view('error401'), 401,$headers);
        }

        return $next($request);
    }
}
