<?php

namespace Roddy\FirestoreEloquent\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class F_Authentication
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /**
         * Middleware to authenticate user session.
         *
         * If the user session is not authenticated, it redirects to the login page.
         * It also sets cache control headers to prevent caching of sensitive data.
         *
         * @param  \Illuminate\Http\Request  $request
         * @param  \Closure  $next
         * @return mixed
         */
        if ($request->session()->get('authUserId') === null) {
            $request->session()->put('fromUrl', $request->fullUrl());

            return redirect()->route(config('firebase.auth_url') ?? 'login');
        }

        $response = $next($request);
        $response->headers->set('Cache-Control', 'nocache, no-store, max-age=0, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', 'Sun, 02 Jan 1990 00:00:00 GMT');

        return $response;
    }
}
