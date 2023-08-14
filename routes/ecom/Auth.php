<?php

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

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


Route::post('/register', function (Request $request) {
    try {
        $validator = Validator::make($request->all(), [
            'name' => ['required'],
            'email' => ['required'],
            'password' => 'required|min:6|confirmed',
            'password_confirmation' => 'required|min:6',
            'agree' => ['required'],
        ]);

        if ($validator->fails()) {
            return make_validation_error_response($validator->getMessageBag());
        }

        $customer = new Customer();
        $customer->name = $request->name;
        $customer->email = $request->email;
        $customer->password = Hash::make($request->password);
        $customer->save();

        $customer->api_token = $customer->id . '|' . Str::random(32);
        $customer->update();

        Cache::put('customer_' . $customer->id, $customer->toArray(), now()->addDays(7));

        return make_success_response("Register successfully.", [
            'customer' => $customer,
            'token' => $customer->api_token
        ]);
    } catch (Exception $exception) {
        return make_error_response($exception->getMessage());
    }
});

Route::post('/login', function (Request $request) {
    try {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if ($validator->fails()) {
            return make_validation_error_response($validator->getMessageBag());
        }

        $customer = Customer::where('email', $request->email)->first();
        if (empty($customer)) throw new Exception("Customer not found.");

        /*if (Hash::check($request->password, $customer->password)) {
            $customer->api_token = $customer->id . '|' . Str::random(32);
            $customer->update();
        }*/

        if (Hash::check($request->password, $customer->password)) {
            $customer->api_token = $customer->id . '|' . Str::random(32);
            $customer->update();
        } else {
            return throw new Exception("Passwords do not match!");
        }

        Cache::put('customer_' . $customer->id, $customer->toArray(), now()->addDays(7));

        return make_success_response("Login successfully.", [
            'customer' => $customer,
            'token' => $customer->api_token
        ]);
    } catch (Exception $exception) {
        return make_error_response($exception->getMessage());
    }
});

Route::post('/logout', function (Request $request) {
    try {
        $token = $request->header('authorization');

        if (!$token) {
            throw new Exception("Token not found!");
        }

        if ($token) {
            list($id) = explode('|', $token);
            Cache::forget('customer_' . $id);
        }

        return make_success_response("Logout successful.");
    } catch (Exception $exception) {
        return make_error_response($exception->getMessage());
    }
});

Route::group(['middleware' => 'isCustomer'], function () {
    Route::put('/change-password', function (Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string|min:6',
                'password' => 'required|string|min:6|confirmed',
                'password_confirmation' => 'required|string|min:6',
            ]);

            if ($validator->fails()) {
                return make_validation_error_response($validator->getMessageBag());
            }

            $customer = Customer::find(auth_customer('id'));
            if (!$customer) throw new Exception("Customer not found!");

            if (Hash::check($request->current_password, $customer->password) === False) {
                throw new Exception("Current password is incorrect.");
            }

            $customer->update([
                'password' => Hash::make($request->password),
            ]);
        } catch (Exception $exception) {
            return make_error_response($exception->getMessage());
        }

        return make_success_response("Password changed successfully.");
    });
});
