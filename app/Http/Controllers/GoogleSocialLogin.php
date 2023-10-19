<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tymon\JWTAuth\Facades\JWTAuth;

class GoogleSocialLogin extends Controller
{
    /**
     * @return string|void
     */
    public function login()
    {
        try {
            $url = config('services.google.auth_uri') . '?' . http_build_query([
                    'client_id' => config('services.google.client_id'),
                    'redirect_uri' => config('services.google.redirect_uri'),
                    'response_type' => 'code',
                    'scope' => 'openid profile email',
                    'state' => config('services.google.state'),
                ]);

            return response()->json([
                'url' => $url
            ]);
        } catch (\Exception $exception) {
            report($exception);

            make_error_response($exception->getMessage());
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|void
     */
    public function callback(Request $request)
    {
        try {
            if ($request->input('state') !== config('services.google.state')) {
                throw new \Exception("Invalid state parameter.");
            }

            $code = $request->input('code');

            $tokenResponse = Http::withOptions(["verify" => false])
                ->post(config('services.google.token_uri'), [
                    'code' => $code,
                    'client_id' => config('services.google.client_id'),
                    'client_secret' => config('services.google.client_secret'),
                    'redirect_uri' => config('services.google.redirect_uri'),
                    'grant_type' => 'authorization_code',
                ]);

            $accessToken = $tokenResponse->json('access_token');

            $customerResponse = Http::withOptions(["verify" => false])
                ->withHeaders([
                    'Authorization' => 'Bearer ' . urlencode($accessToken),
                ])
                ->get(config('services.google.userinfo_uri'));

            $customerData = $customerResponse->json();

            $name = $customerData['name'];
            $email = $customerData['email'];

            $customer = Customer::where('email', $email)->first();

            if (!$customer) {
                $customer = Customer::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]);
            }

            Auth::login($customer);

            $token = JWTAuth::fromUser($customer);
            if (!$token) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            return make_success_response("Login successfully.", [
                'token' => 'Bearer ' . $token,
                'customer' => Auth::user(),
                'expires_in' => Auth::factory()->getTTL() * 60 * 24,
            ]);
        } catch (\Exception $exception) {
            report($exception);

            make_error_response($exception->getMessage());
        }
    }
}
