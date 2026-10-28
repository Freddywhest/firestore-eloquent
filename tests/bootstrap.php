<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use Illuminate\Container\Container;

if (!function_exists('config')) {
    function config(?string $key = null, mixed $default = null): mixed
    {
        $repository = Container::getInstance()->make('config');
        return $key === null ? $repository : $repository->get($key, $default);
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }
}
