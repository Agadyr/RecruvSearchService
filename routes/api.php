<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ArticlesController;
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

Route::get('/reIndex', [ArticlesController::class, 'reIndexArticles']);
Route::post('/search',[ArticlesController::class, 'searchInArticlesIndex']);

Route::get('/updateAllUsers', [UsersController::class, 'index']);
Route::post('/createUser', [UsersController::class, 'store']);
Route::post('/createMessage', [UsersController::class, 'createMessage']);
Route::post('/giveSuggestions', [UsersController::class, 'giveSuggestions']);
