<?php

use App\Http\Controllers\KhsController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiAuthController;
use App\Http\Controllers\KrsController;

Route::post('/login', [ApiAuthController::class, 'login'])->middleware('throttle:5,1');

// amankan pakai token
Route::middleware(['api_token'])->group(function () {
    Route::get('/profile', [ApiAuthController::class, 'profile']);
    Route::post('/logout', [ApiAuthController::class, 'logout']);

    //Student
    Route::get('/students', [StudentController::class, 'studentData']);
    Route::get('/student-krs', [KrsController::class, 'studentKrs']);
    Route::get('/student-khs', [KhsController::class, 'studentKhs']);

    //master
    Route::prefix('master')->group(function () {
        Route::get('departments', [MasterController::class, 'departments']);
        Route::get('program-classes', [MasterController::class, 'classPrograms']);
        Route::get('religions', [MasterController::class, 'religions']);
        Route::get('marital-statuses', [MasterController::class, 'maritalStatuses']);
    });
});
