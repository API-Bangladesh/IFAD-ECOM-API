<?php

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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

    Route::get('/orders', function (Request $request) {
        try {
            $query = Order::query();
            $query->with('customer', 'paymentMethod', 'orderItems');
            $query->where('customer_id', auth_customer('id'));

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

    Route::get('/orders/{id}/show', function ($id) {
        try {
            return Order::with('customer', 'paymentMethod', 'orderItems')->findOrFail($id);
        } catch (Exception $exception) {
            return make_error_response($exception->getMessage());
        }
    });

    Route::post('/orders', function (Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'shipping_address' => ['required'],
                'billing_address' => ['required'],
                'payment_method_id' => ['required'],
                'cart' => ['required'],
                'sub_total' => ['required'],
                'grand_total' => ['required']
            ]);

            if ($validator->fails()) {
                return make_validation_error_response($validator->getMessageBag());
            }

            $order = new Order();
            $order->order_date = Carbon::now();
            $order->customer_id = auth_customer('id');
            $order->shipping_address = $request->shipping_address;
            $order->billing_address = $request->billing_address;
            $order->sub_total = $request->sub_total;
            $order->discount = 0;
            $order->shipping_charge = 0;
            $order->tax = 0;
            $order->grand_total = $request->grand_total;
            $order->payment_method_id = $request->payment_method_id;
            $order->payment_details = json_encode([]);
            $order->order_status_id = Order::ORDER_STATUS_PENDING;
            $order->payment_status_id = Order::PAYMENT_STATUS_UNPAID;
            $order->save();

            $total = 0;
            foreach ($request->cart as $item) {
                if (empty($item['inventory_id'])) continue;
                $total += $item['quantity'] * $item['unit_price'];

                $orderItem = new OrderItem();
                $orderItem->type = $item['type'];
                $orderItem->order_id = $order->id;
                $orderItem->inventory_id = isset($item['inventory_id']) ? $item['inventory_id'] : null;
                $orderItem->combo_id = isset($item['combo_id']) ? $item['combo_id'] : null;
                $orderItem->quantity = $item['quantity'];
                $orderItem->unit_price = $item['unit_price'];
                $orderItem->save();
            }

            $order->sub_total = $total;
            $order->grand_total = $total;
            $order->update();

            return make_success_response("Record saved successfully.");
        } catch (Exception $exception) {
            return make_error_response($exception->getMessage());
        }
    });
});

