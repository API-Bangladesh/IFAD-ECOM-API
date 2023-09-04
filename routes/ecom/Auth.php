<?php

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

Route::post('/register', 'AuthController@register');
Route::post('/login', 'AuthController@login');
Route::post('/forgot-password', 'AuthController@forgotPassword');
Route::post('/reset-password', 'AuthController@resetPassword');

Route::group(['middleware' => 'auth:api'], function () {
    Route::put('/change-password', 'AuthController@changePassword');
    Route::post('/logout', 'AuthController@logout');
});
