<?php
/*
 * Copyright 2023 Alfred Nti
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @package Roddy\FirestoreEloquent\Firestore\Eloquent\traits
 */

namespace Roddy\FirestoreEloquent\Auth;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class FAuth
{
    /**
     * Set the class name to be used for authentication.
     * If the class does not exist, return an error message.
     */
    private static $className;

    /**
     * FAuth constructor.
     * Set the class name to be used for authentication.
     * If the class does not exist, return an error message.
     */
    public function __construct()
    {
        $className = config('firebase.auth_model') ?? 'App\FModels\User';

        if (!class_exists($className)) {

            $class = explode('\\', $className);

            return trigger_error(
                'Model "' . end($class) . '" does not exists. ' .
                    'Run artisan command: "php artisan make:fmodel ' . end($class) . '" to create "' . end($class) . '" model. You can check the documentation for help.',
                E_USER_NOTICE
            );
        } else {
            self::$className = $className;
        }
    }

    /**
     * Attempt to authenticate a user with the given email and password.
     * @param array $args An array containing email and password.
     * @return bool Returns true if the authentication is successful, false otherwise.
     */
    public static function attempt(array $args): bool
    {
        new self();
        $className = self::$className;

        ["email" => $email, "password" => $password] = $args;
        $user = $className::where(['email', '=', $email])->first();

        if (!$user->exists()) {
            return false;
        }

        if (!Hash::check($password, $user->password)) {
            return false;
        }
        Session::put('authUserId', $user->{(new $className)->primaryKey});
        return true;
    }

    /**
     * Get the authenticated user.
     * @return object|null Returns an object representing the authenticated user, or null if no user is authenticated.
     */
    public static function user(): ?object
    {
        new self();
        $className = self::$className;

        if (request()->session()->get("authUserId")) {
            $user = $className::where([(new $className)->primaryKey, '=', request()->session()->get("authUserId")])->first();
            unset($user->password);
        } else {
            return null;
        }
        return (object) $user;
    }

    /**
     * Check if a user is authenticated.
     * @return bool|null Returns true if a user is authenticated, false otherwise.
     */
    public static function check(): ?bool
    {
        new self();
        return Session::get("authUserId")
            ? true
            : false;
    }

    /**
     * Log out the authenticated user.
     */
    public static function logout(): void
    {
        new self();
        request()->session()->remove('authUserId');
    }

    /**
     * Get the ID of the authenticated user.
     * @return mixed|null Returns the ID of the authenticated user, or null if no user is authenticated.
     */
    public static function id()
    {
        new self();
        return request()->session()->get('authUserId');
    }
}
