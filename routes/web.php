<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/server-check', function () {
    return "You are on PORT: " . request()->getPort();
});
