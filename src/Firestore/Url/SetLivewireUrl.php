<?php

namespace Roddy\FirestoreEloquent\Firestore\Url;

use Closure;
use Illuminate\Http\Request;
use Livewire\LivewireManager;

class SetLivewireUrl
{
    /**
     * Set the current URL in session for Firestore Eloquent.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if ($this->isLivewireRequest()) {
            return $next($request);
        }

        if ($this->isMethodNotSupported($request)) {
            return $next($request);
        }

        if(session()->has('roddy-eloquent-urls.current')){
            session()->forget('roddy-eloquent-urls.current');
        }

        session()->put('roddy-eloquent-urls.current', $request->fullUrl());

        return $next($request);
    }

    /**
     * Determine if the current request is a Livewire request.
     *
     * @return bool
     */
    protected function isLivewireRequest(): bool
    {
        return class_exists(LivewireManager::class) && app(LivewireManager::class)->isLivewireRequest();
    }

    /**
     * Check if the request method is not supported.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function isMethodNotSupported(Request $request): bool
    {
        return ! in_array($request->method(), ['GET']);
    }
}
