<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

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

Route::group(['middleware' => 'isCustomer'], function () {

    /**
     *
     */
    Route::get('/cart', function () {
        try {
            return Session::get('cart', []);
        } catch (Exception $exception) {
            return make_error_response($exception->getMessage());
        }
    });

    /**
     *
     */
    Route::get('/cart/total', function () {
        try {
            $cart = Session::get('cart', []);

            $total = 0;

            foreach ($cart as $item){
                $total += $item['total'];
            }

            return $total;
        } catch (Exception $exception) {
            return make_error_response($exception->getMessage());
        }
    });

    /**
     *
     */
    Route::post('/cart', function (Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'inventory_id' => ['required'],
                'quantity' => ['required'],
                'unit_price' => ['required'],
                'product_type' => ['required'],
                'product_name' => ['required'],
                'product_image' => ['required'],
            ]);

            if ($validator->fails()) {
                return make_validation_error_response($validator->getMessageBag());
            }

            $cart = Session::get('cart', []);

            $new = [
                'id' => rand(11111111, 99999999),
                'inventory_id' => $request->inventory_id,
                'quantity' => $request->quantity,
                'unit_price' => $request->unit_price,
                'total' => $request->quantity * $request->unit_price,

                'product_type' => $request->product_type,
                'product_name' => $request->product_name,
                'product_category_name' => $request->product_category_name,
                'product_sub_category_name' => $request->product_sub_category_name,
                'product_image' => $request->product_image,
                'product_variations' => $request->product_variations
            ];

            $cart[] = $new;

            Session::put('cart', $cart);

            return make_success_response("Record saved successfully.");
        } catch (Exception $exception) {
            return make_error_response($exception->getMessage());
        }
    });

    /**
     *
     */
    Route::get('/cart/{id}/show', function ($id) {
        try {
            $cart = Session::get('cart', []);

            $_item = [];
            foreach ($cart as $item) {
                if ($item['id'] == $id) {
                    $_item = $item;
                    break;
                }
            }

            return $_item;
        } catch (Exception $exception) {
            return make_error_response($exception->getMessage());
        }
    });

    /**
     *
     */
    Route::put('/cart/{id}', function (Request $request, $id) {
        try {
            $validator = Validator::make($request->all(), [
                'quantity' => ['required']
            ]);

            if ($validator->fails()) {
                return make_validation_error_response($validator->getMessageBag());
            }

            $cart = Session::get('cart');

            foreach ($cart as $key => $item){
                if($item['id'] == $id){
                    $newQty = $item['quantity'] + $request->quantity;

                    $cart[$key]['quantity'] = $newQty;
                    $cart[$key]['total'] = $item['unit_price'] * $newQty;
                }
            }

            Session::put('cart', $cart);

            return make_success_response("Record updated successfully.");
        } catch (Exception $exception) {
            return make_error_response($exception->getMessage());
        }
    });

    /**
     *
     */
    Route::delete('/cart/{id}/delete', function ($id) {
        try {
            $cart = Session::get('cart', []);

            $cart = array_filter($cart, function ($item) use ($id){
                return $item['id'] != $id;
            });

            $new = array_values($cart);

            Session::put('cart', $new);

            return make_success_response("Record deleted successfully.");
        } catch (Exception $exception) {
            return make_error_response($exception->getMessage());
        }
    });

    /**
     *
     */
    Route::delete('/cart/reset', function () {
        try {
            Session::forget('cart');

            return make_success_response("Record reset successfully.");
        } catch (Exception $exception) {
            return make_error_response($exception->getMessage());
        }
    });
});
