<?php

namespace App\Http\Middleware;

use App\Models\Customer;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class IsCustomerMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (!$request->header('authorization')) {
            abort(401);
        }

        if (Session::has('customer')) {
            return $next($request);
        }

        $customer = Customer::where('api_token', $request->header('authorization'))->first();
        if (!$customer) {
            abort(401);
        }

        Session::put('customer', $customer);

        return $next($request);
    }
}
