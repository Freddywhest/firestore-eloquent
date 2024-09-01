<?php

use Roddy\FirestoreEloquent\Auth\FAuth;

/**
 * Get a new instance of FAuth.
 *
 * @return \FAuth
 */
if (! function_exists('fauth')) {
    function fauth()
    {
        return new FAuth;
    }
}
