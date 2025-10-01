<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // $hash = Hash::make('password');
    // dd($hash);
    return view('welcome');
});
