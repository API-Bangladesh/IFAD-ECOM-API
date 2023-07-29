<?php

use App\Models\PaymentMethod;
use Illuminate\Http\Request;
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


Route::get('/payment-methods', function (Request $request) {
    try {
        return PaymentMethod::where('status', PaymentMethod::STATUS_ACTIVE)->get();
    } catch (Exception $exception) {
        return make_error_response($exception->getMessage());
    }
});


Route::get('/payment-methods/{id}/show', function ($id) {
    try {
        return PaymentMethod::findOrFail($id);
    } catch (Exception $exception) {
        return make_error_response($exception->getMessage());
    }
});
