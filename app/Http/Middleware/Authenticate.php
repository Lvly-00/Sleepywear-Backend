<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\JsonResponse;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo($request): ?string
    {
        if (! $request->expectsJson()) {
            return route('login');
        }

        return null;
    }

    /**
     * Handle unauthenticated requests for API.
     */
    protected function unauthenticated($request, array $guards)
    {
        // If it's an API request, return JSON instead of redirecting
        if ($request->expectsJson()) {
            abort(new JsonResponse(['message' => 'Unauthenticated.'], 401));
        }

        // Otherwise, fallback to the default web redirect
        parent::unauthenticated($request, $guards);
    }
}
