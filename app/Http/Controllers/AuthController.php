<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
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

            $credentials = $request->only(['email', 'password']);

            if (!$token = Auth::attempt($credentials)) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            return make_success_response("Login successfully.", [
                'token' => 'Bearer ' . $token,
                'customer' => auth()->user(),
                'expires_in' => auth()->factory()->getTTL() * 60 * 24,
            ]);
        } catch (Exception $exception) {
            return make_error_response($exception->getMessage());
        }
    }

    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => ['required', 'email'],
                'password' => ['required'],
            ]);

            if ($validator->fails()) {
                return make_validation_error_response($validator->getMessageBag());
            }

            $credentials = $request->only(['email', 'password']);

            if (!$token = Auth::attempt($credentials)) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            return make_success_response("Login successfully.", [
                'token' => 'Bearer ' . $token,
                'customer' => auth()->user(),
                'expires_in' => auth()->factory()->getTTL() * 60 * 24,
            ]);
        } catch (Exception $exception) {
            return make_error_response($exception->getMessage());
        }
    }

    public function changePassword(Request $request)
    {
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
    }

    public function logout()
    {
        try {
            Auth::logout();

            return make_success_response("Logout successful.");
        } catch (Exception $exception) {
            return make_error_response($exception->getMessage());
        }
    }
}
