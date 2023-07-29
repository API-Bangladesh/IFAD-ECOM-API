<?php

use App\Models\Order;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

/**
 * @return array[]
 */
function get_order_statuses()
{
    return [
        [
            'id' => Order::ORDER_STATUS_PENDING,
            'text' => "Pending"
        ],
        [
            'id' => Order::ORDER_STATUS_PROCESSING,
            'text' => "Processing"
        ],
        [
            'id' => Order::ORDER_STATUS_SHIPPED,
            'text' => "Shipped"
        ],
        [
            'id' => Order::ORDER_STATUS_DELIVERED,
            'text' => "Delivered"
        ],
        [
            'id' => Order::ORDER_STATUS_CANCELED,
            'text' => "Canceled"
        ]
    ];
}

/**
 * @param $id
 * @return string
 */
function get_order_status_name($id)
{
    $statuses = get_order_statuses();

    foreach ($statuses as $status) {
        if ($status['id'] == $id) {
            return $status['text'];
        }
    }

    return '';
}

/**
 * @return array[]
 */
function get_payment_statuses()
{
    return [
        [
            'id' => Order::PAYMENT_STATUS_PAID,
            'text' => "Paid"
        ],
        [
            'id' => Order::PAYMENT_STATUS_UNPAID,
            'text' => "Unpaid"
        ]
    ];
}

/**
 * @param $id
 * @return string
 */
function get_payment_status_name($id)
{
    $statuses = get_payment_statuses();

    foreach ($statuses as $status) {
        if ($status['id'] == $id) {
            return $status['text'];
        }
    }

    return '';
}

/**
 * @param string $message
 * @param array $result
 * @param int $code
 * @param string $redirect
 * @param string $delay
 * @return \Illuminate\Http\JsonResponse
 */
function make_success_response($message = '', $result = [], $code = 200, $redirect = '', $delay = '')
{
    $response = [
        'status' => True,
        'message' => $message
    ];

    if (!empty($result)) {
        $response['data'] = $result;
    }
    if (!empty($redirect)) {
        $response['redirect'] = $redirect;
    }
    if (!empty($delay)) {
        $response['delay'] = $delay;
    }

    return response()->json($response, $code);
}

/**
 * @param string $message
 * @param array $errors
 * @param int $code
 * @param string $redirect
 * @param string $delay
 * @return \Illuminate\Http\JsonResponse
 */
function make_error_response($message = '', $errors = [], $code = 404, $redirect = '', $delay = '')
{
    $response = [
        'status' => False,
        'message' => $message
    ];

    if (!empty($errors)) {
        $response['errors'] = $errors;
    }
    if (!empty($redirect)) {
        $response['redirect'] = $redirect;
    }
    if (!empty($delay)) {
        $response['delay'] = $delay;
    }

    return response()->json($response, $code);
}

/**
 * @param array $errors
 * @param string $message
 * @param int $code
 * @return \Illuminate\Http\JsonResponse
 */
function make_validation_error_response($errors = [], $message = 'The given data was invalid!', $code = 404, $redirect = '')
{
    $response = [
        'status' => False,
        'message' => $message,
        'errors' => $errors,
    ];

    if (!empty($redirect)) {
        $response['redirect'] = $redirect;
    }

    return response()->json($response, $code);
}

/**
 * @param $key
 * @return mixed
 */
function auth_customer($key)
{
    $customer = null;
    $token = request()->header('authorization');

    if ($token) {
        list($id) = explode('|', $token);
        $customer = Cache::get('customer_' . $id);
    }

    return optional($customer)->$key;
}

/**
 * @param null $timezone
 * @return Carbon
 */
function now($timezone = null)
{
    return Carbon::now($timezone);
}
