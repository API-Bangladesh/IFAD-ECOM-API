<?php

namespace App\Http\Controllers;

use App\Mail\SendPasswordResetLink;
use App\Mail\SendVerificationNotificationLink;
use App\Mail\SendVerifiedEmailNotification;
use App\Models\Customer;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => ['required'],
                'email' => ['required', 'email', 'unique:customers,email'],
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

            if ($request->remember) {
                if (!$token = Auth::attempt($credentials, true)) {
                    return response()->json(['message' => 'Unauthorized'], 401);
                }
            } else {
                if (!$token = Auth::attempt($credentials)) {
                    return response()->json(['message' => 'Unauthorized'], 401);
                }
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

            $customer = Customer::find(Auth::id());
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

    public function forgotPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => ['required', 'email']
            ]);

            if ($validator->fails()) {
                return make_error_response($validator->getMessageBag());
            }

            // Set Frontend URL
            $email = $request->email;
            $token = $this->createToken();

            $url = config('app.frontend_url') . "/auth/reset-password/{$token}?email={$email}";

            Mail::to($email)->send(new SendPasswordResetLink($url));
        } catch (\Exception $exception) {
            report($exception);

            return make_success_response($exception->getMessage());
        }

        return make_success_response("Password Reset link has been sent. Please check your email.");
    }

    public function resetPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'token' => ['required'],
                'email' => ['required', 'email'],
                'password' => ['required', 'confirmed', 'min:6'],
            ]);

            if ($validator->fails()) {
                return make_error_response($validator->getMessageBag());
            }

            // Match
            $this->matchToken($request->token);

            $request->only(['email', 'password']);

            $customer = Customer::where('email', $request->email)->first();
            if (!$customer) throw new Exception("Customer not found!");

            $customer->password = Hash::make($request->password);
            $customer->update();

        } catch (\Exception $exception) {
            report($exception);

            return make_error_response($exception->getMessage());
        }

        return make_success_response("Password reset successfully.");
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

    public function createToken()
    {
        return base64_encode(md5('ifadeshop' . date('H')));
    }

    public function matchToken($token)
    {
        if (md5('ifadeshop' . date('H')) != base64_decode($token)) {
            throw new Exception("Token doesn't match!");
        }
    }

    public function verificationNotificationLink(Request $request)
    {
        try {
            $email = $request->user()->email;
            $token = $this->createToken();

            $url = config('app.frontend_url') . "/auth/verify-email/{$token}";

            Mail::to($email)->send(new SendVerificationNotificationLink($url));
        } catch (\Exception $exception) {
            report($exception);

            return make_success_response($exception->getMessage());
        }

        return make_success_response("Verification email sent. Please check your email.");
    }

    public function verifyEmail(Request $request, $token)
    {
        try {
            $this->matchToken($token);

            $email = $request->user()->email;

            $customer = Customer::where('email', $email)->first();
            if (!$customer) throw new Exception("Customer not found!");

            $customer->email_verified_at = now();
            $customer->update();

            Mail::to($email)->send(new SendVerifiedEmailNotification());
        } catch (\Exception $exception) {
            report($exception);

            return make_success_response($exception->getMessage());
        }

        return make_success_response("Your email is verified.");
    }
}
