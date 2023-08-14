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

    Route::post('/orders/make-payment', function (Request $request) {
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

            // make order
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

            // // sending mail
            // $msg = "A product has been ordered from " . $request->shipping_address;

            // // use wordwrap() if lines are longer than 70 characters
            // $msg = wordwrap($msg, 70);

            // // send email
            // mail("typetonazmul@gmail.com", "My subject", $msg);


            // make payment
            /* Store Config */
            $storeId = env('SSL_STORE_ID');
            $storePassword = env('SSL_STORE_PASSWORD');
            $storeApiUrl = env('SSL_API_URL');
            $completion = env('SSL_COMPLETION_URL');

            $post_data = array();
            $post_data['store_id'] = $storeId;
            $post_data['store_passwd'] = $storePassword;
            $post_data['total_amount'] = $request->grand_total;
            $post_data['currency'] = "BDT";
            $post_data['tran_id'] = "ifadshop".uniqid();
            $post_data['success_url'] = $completion . "?status=success";
            $post_data['fail_url'] = $completion . "?status=fail";
            $post_data['cancel_url'] = $completion . "?status=cancel";
            # $post_data['multi_card_name'] = "mastercard,visacard,amexcard";  # DISABLE TO DISPLAY ALL AVAILABLE

            # EMI INFO
            $post_data['emi_option'] = "1";
            $post_data['emi_max_inst_option'] = "9";
            $post_data['emi_selected_inst'] = "9";

            // # CUSTOMER INFORMATION
            // $post_data['cus_name'] = "Test Customer";
            // $post_data['cus_email'] = "test@test.com";
            // $post_data['cus_add1'] = "Dhaka";
            // $post_data['cus_add2'] = "Dhaka";
            // $post_data['cus_city'] = "Dhaka";
            // $post_data['cus_state'] = "Dhaka";
            // $post_data['cus_postcode'] = "1000";
            // $post_data['cus_country'] = "Bangladesh";
            // $post_data['cus_phone'] = "01711111111";
            // $post_data['cus_fax'] = "01711111111";

            // # SHIPMENT INFORMATION
            // $post_data['ship_name'] = "Store Test";
            // $post_data['ship_add1 '] = "Dhaka";
            // $post_data['ship_add2'] = "Dhaka";
            // $post_data['ship_city'] = "Dhaka";
            // $post_data['ship_state'] = "Dhaka";
            // $post_data['ship_postcode'] = "1000";
            // $post_data['ship_country'] = "Bangladesh";

            // # OPTIONAL PARAMETERS
            // $post_data['value_a'] = "ref001";
            // $post_data['value_b '] = "ref002";
            // $post_data['value_c'] = "ref003";
            // $post_data['value_d'] = "ref004";

            // # CART PARAMETERS
            // $post_data['cart'] = json_encode(array(
            //     array("product"=>"DHK TO BRS AC A1","amount"=>"200.00"),
            //     array("product"=>"DHK TO BRS AC A2","amount"=>"200.00"),
            //     array("product"=>"DHK TO BRS AC A3","amount"=>"200.00"),
            //     array("product"=>"DHK TO BRS AC A4","amount"=>"200.00")
            // ));
            // $post_data['product_amount'] = "100";
            // $post_data['vat'] = "5";
            // $post_data['discount_amount'] = "5";
            // $post_data['convenience_fee'] = "3";


            // //////////////////////////////////
            # REQUEST SEND TO SSLCOMMERZ
            $direct_api_url = $storeApiUrl;

            $handle = curl_init();
            curl_setopt($handle, CURLOPT_URL, $direct_api_url );
            curl_setopt($handle, CURLOPT_TIMEOUT, 30);
            curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 30);
            curl_setopt($handle, CURLOPT_POST, 1 );
            curl_setopt($handle, CURLOPT_POSTFIELDS, $post_data);
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, FALSE); # KEEP IT FALSE IF YOU RUN FROM LOCAL PC

            $content = curl_exec($handle);

            $code = curl_getinfo($handle, CURLINFO_HTTP_CODE);

            if($code == 200 && !( curl_errno($handle))) {
                curl_close($handle);
                $sslcommerzResponse = $content;
            } else {
                curl_close($handle);
                // echo "FAILED TO CONNECT WITH SSLCOMMERZ API";
                return response()->json(['error' => 'FAILED TO CONNECT WITH SSLCOMMERZ API']);
            }

            # PARSE THE JSON RESPONSE
            $sslcz = json_decode($sslcommerzResponse, true );

            if (isset($sslcz['GatewayPageURL']) && $sslcz['GatewayPageURL'] !== "") {
                return response()->json(['GatewayPageURL' => $sslcz['GatewayPageURL']]);
            } else {
                return response()->json(['error' => 'JSON Data parsing error']);
            }

            // return;
            // return make_success_response("Payment done successfully.");
        } catch (Exception $exception) {
            return make_error_response($exception->getMessage());
        }
    });
});

