<?php

use App\Models\Address;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
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

    Route::get('/customers', function (Request $request) {
        try {
            $query = Customer::query();
            return $query->paginate($request->get('limit', 15));
        } catch (Exception $exception) {
            return make_error_response($exception->getMessage());
        }
    });

    Route::get('/customers/{id}/show', function ($id) {
        try {
            return Customer::findOrFail($id);
        } catch (Exception $exception) {
            return make_error_response($exception->getMessage());
        }
    });

    Route::put('/customers', function (Request $request) {
        try {
            $rules = [
                'name' => ['required'],
                'date_of_birth' => ['nullable'],
                'gender' => ['nullable'],
                'phone_number' => ['required'],
            ];

            if ($request->hasFile('image')) {
                $rules['image'] = ['image', 'mimes:jpg,png,jpeg,gif', 'max:2048'];
            }

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return make_validation_error_response($validator->getMessageBag());
            }

            $customer = Customer::findOrFail(auth_customer('id'));
            $customer->name = $request->name;
            $customer->date_of_birth = $request->date_of_birth;
            $customer->gender = $request->gender;
            $customer->phone_number = $request->phone_number;

            if ($request->hasFile('image')) {
                $dir = 'uploads/' . date('Y') . '/' . date('m');

                if ($request->filled('old_image')) {
                    File::delete($request->old_image);
                }

                $image = $request->file('image');
                $imageName = time() . '_' . $image->getClientOriginalName();
                $image->move($dir, $imageName);
                $customer->image = "$dir/$imageName";
            }

            $customer->update();

            return make_success_response("Record saved successfully.", $customer);
        } catch (Exception $exception) {
            return make_error_response($exception->getMessage());
        }
    });

    Route::get('/customers/addresses', function () {
        try {
            return Address::where('customer_id', auth_customer('id'))->paginate();
        } catch (Exception $exception) {
            return make_error_response($exception->getMessage());
        }
    });
});
