<?php

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // $hash = Hash::make('sibermuapiX7dL9mQ2vT5gH4kN8z');
    // dd($hash);
    return view('welcome');
});
