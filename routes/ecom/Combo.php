<?php

use App\Http\Controllers\ComboController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::get('/combos', [ComboController::class, 'index']);
Route::get('/combos/{id}/show', [ComboController::class, 'show']);
Route::get('/combos/combo-categories/{comboCategoryId}', [ComboController::class, 'getByComboCategory']);
Route::get('/combos/search', [ComboController::class, 'search']);
