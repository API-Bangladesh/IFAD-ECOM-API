<?php

use App\Models\Order;
use App\Models\Review;
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

Route::group([], function () {

    /**
     *
     */
    Route::get('/reviews', function (Request $request) {
        try {
            $query = Review::query();
            $query->with('customer', 'inventory', 'combo');

            $query->when($request->limit, function ($q) use ($request) {
                $q->limit($request->limit);
            });

            if ($request->paginate === 'yes') {
                return $query->paginate($request->get('limit', 15));
            } else {
                return $query->get();
            }
        } catch (Exception $exception) {
            return make_error_response($exception->getMessage());
        }
    });

    /**
     *
     */
    Route::get('/reviews/inventories/{inventoryId}', function (Request $request, $inventoryId) {
        try {
            $query = Review::query();
            $query->with('customer', 'inventory', 'combo');
            $query->where('inventory_id', $inventoryId);

            $query->when($request->limit, function ($q) use ($request) {
                $q->limit($request->limit);
            });

            if ($request->paginate === 'yes') {
                return $query->paginate($request->get('limit', 15));
            } else {
                return $query->get();
            }
        } catch (Exception $exception) {
            return make_error_response($exception->getMessage());
        }
    });


    /**
     *
     */
    Route::get('/reviews/{id}/show', function ($id) {
        try {
            return Review::with('customer', 'inventory', 'combo')->findOrFail($id);
        } catch (Exception $exception) {
            return make_error_response($exception->getMessage());
        }
    });

    /**
     *
     */
    Route::post('/reviews', function (Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'ratting_number' => ['required', 'numeric'],
                'comments' => ['required'],
                'customer_id' => ['required', 'numeric'],
                'inventory_id' => ['nullable', 'numeric'],
                'combo_id' => ['nullable', 'numeric'],
            ]);

            if ($validator->fails()) {
                return make_validation_error_response($validator->getMessageBag());
            }

            $isExists = Review::where('customer_id', $request->customer_id)
                ->where('inventory_id', $request->inventory_id)->exists();

            $orIsExists = Review::where('customer_id', $request->customer_id)
                ->where('combo_id', $request->combo_id)->exists();

            if ($isExists || $orIsExists) {
                return make_error_response("Already existed.");
            }

            $review = new Review();
            $review->customer_id = $request->customer_id;
            $review->inventory_id = $request->inventory_id;
            $review->combo_id = $request->combo_id;
            $review->status = Review::STATUS_PENDING;
            $review->save();

            return make_success_response("Record saved successfully. Waiting for approved!");
        } catch (Exception $exception) {
            return make_error_response($exception->getMessage());
        }
    });

    /**
     *
     */
    Route::delete('/reviews/{id}', function ($id) {
        try {
            $reviews = Review::findOrFail($id);

            if ($reviews) {
                $reviews->delete();
            }

            return make_success_response("Record deleted successfully.");
        } catch (Exception $exception) {
            return make_error_response($exception->getMessage());
        }
    });

    Route::get('inventories/{inventoryId}/reviews/ability', function ($inventoryId) {
        try {
            $exists = Order::where('customer_id', auth_customer('id'))->whereHas('orderItems', function ($query) use ($inventoryId) {
                $query->where('inventory_id', $inventoryId);
            })->exists();

            if ($exists) {
                return response()->json([
                    "capability" => true
                ]);
            }

            return response()->json([
                "capability" => false
            ]);
        } catch (Exception $exception) {
            return make_error_response($exception->getMessage());
        }
    });

    Route::get('combos/{comboId}/reviews/ability', function ($comboId) {
        try {
            $exists = Order::where('customer_id', auth_customer('id'))->whereHas('orderItems', function ($query) use ($comboId) {
                $query->where('combo_id', $comboId);
            })->exists();

            if ($exists) {
                return response()->json([
                    "capability" => true
                ]);
            }

            return response()->json([
                "capability" => false
            ]);
        } catch (Exception $exception) {
            return make_error_response($exception->getMessage());
        }
    });
});
