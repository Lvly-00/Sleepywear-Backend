<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

Route::get('/debug-csrf', function () {
    return response()->json([
        'csrf_token' => csrf_token(),
        'session_id' => Session::getId(),
        'cookies' => request()->cookies->all(),
    ]);
});
