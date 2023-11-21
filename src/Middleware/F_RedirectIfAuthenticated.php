<?php

namespace Roddy\FirestoreEloquent\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class F_RedirectIfAuthenticated
{
    public const HOME = 'home';
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /**
         * Redirects the user to the guest URL if they are already authenticated.
         *
         * @param  \Illuminate\Http\Request  $request
         * @return \Illuminate\Http\RedirectResponse|null
         */
        if($request->session()->get('authUserId') != null || !empty($request->session()->get('authUserId'))){
            return redirect()->route(config('firebase.guest_url') ?? self::HOME);
        }
        return $next($request);
    }
}
