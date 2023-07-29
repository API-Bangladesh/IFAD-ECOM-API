<?php

use App\Models\Customer;
use App\Models\Wishlist;
use Illuminate\Http\Request;
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

Route::group(['middleware' => 'auth'], function () {

    /**
     *
     */
    Route::get('/wishlist', function (Request $request) {
        try {
            $query = Wishlist::query();
            $query->with('customer', 'inventory', 'combo');

            return $query->paginate($request->get('limit', 15));
        } catch (Exception $exception) {
            return make_error_response($exception->getMessage());
        }
    });

    /**
     *
     */
    Route::get('/wishlist/{id}/show', function ($id) {
        try {
            return Wishlist::with('customer', 'inventory', 'combo')->findOrFail($id);
        } catch (Exception $exception) {
            return make_error_response($exception->getMessage());
        }
    });

    /**
     *
     */
    Route::post('/wishlist/sync', function (Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'inventory_id' => ['required', 'numeric']
            ]);

            if ($validator->fails()) {
                return make_validation_error_response($validator->getMessageBag());
            }

            $isExists = Wishlist::where('customer_id', $request->customer_id)
                ->where('inventory_id', $request->inventory_id)->exists();

            if ($isExists) {
                return make_error_response("Already existed.");
            }

            $wishlist = Wishlist::where('customer_id', auth_customer('id'))
                ->where('inventory_id', $request->inventory_id)->first();

            if ($wishlist) {
                $wishlist->delete();

                return make_success_response("Record deleted successfully.", [
                    'favourite' => false
                ]);
            }

            Wishlist::create([
                'customer_id' => auth_customer('id'),
                'inventory_id' => $request->inventory_id
            ]);

            return make_success_response("Record saved successfully.", [
                'favourite' => true
            ]);
        } catch (Exception $exception) {
            return make_error_response($exception->getMessage());
        }
    });

    /**
     *
     */
    Route::get('/wishlist/inventories/{inventoryId}/status', function (Request $request, $inventoryId) {
        try {
            $wishlist = Wishlist::where('customer_id', auth_customer('id'))
                ->where('inventory_id', $inventoryId)->first();

            if ($wishlist) {
                return response()->json([
                    'favourite' => true
                ]);
            }

            return response()->json([
                'favourite' => false
            ]);
        } catch (Exception $exception) {
            return make_error_response($exception->getMessage());
        }
    });

    /**
     *
     */
    Route::delete('/wishlist/{id}', function ($id) {
        try {
            $wishlist = Wishlist::findOrFail($id);

            if ($wishlist) {
                $wishlist->delete();
            }

            return make_success_response("Record deleted successfully.");
        } catch (Exception $exception) {
            return make_error_response($exception->getMessage());
        }
    });
});
