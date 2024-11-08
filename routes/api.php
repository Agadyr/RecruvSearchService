<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VacanciesController;
use App\Http\Controllers\UsersController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


Route::prefix('users')->group(function () {
    Route::get('/updateAllUsers', [UsersController::class, 'index']);
    Route::post('/createUser', [UsersController::class, 'store']);
    Route::post('/createMessage', [UsersController::class, 'createMessage']);
    Route::post('/giveSuggestions', [UsersController::class, 'giveSuggestions']);
});


Route::prefix('vacancies')->group(function () {
    Route::post('addNewVacancyToIndex', [VacanciesController::class, 'create']);
    Route::get('/createOrResetIndex', [VacanciesController::class, 'reCreateVacanciesIndex']);
    Route::post('/searchVacanciesByParams', [VacanciesController::class, 'search']);
});


Route::prefix('/resumes')->group(function () {
    Route::get('/createOrResetIndex', [\App\Http\Controllers\ResumeController::class, 'reCreateVacanciesIndex']);
});
