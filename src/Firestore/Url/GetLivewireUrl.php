<?php

namespace Roddy\FirestoreEloquent\Firestore\Url;

class GetLivewireUrl
{
    /**
     * Get the current URL from session or fallback to provided URL.
     *
     * @param  string|null  $fallback  The fallback URL.
     * @return string|null The current URL or null if not found.
     */
    public static function current(?string $fallback = null): ?string
    {
        if (session()->has('roddy-eloquent-urls.current')) {
            return session()->get('roddy-eloquent-urls.current', $fallback);
        }

        return null;
    }
}
