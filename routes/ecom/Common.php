<?php

use App\Models\B2B;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
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

Route::post('/send-contact-form', function (Request $request) {
    try {
        $validator = Validator::make($request->all(), [
            'name' => ['required'],
            'email' => ['required', 'email'],
            'subject' => ['required'],
            'message' => ['required']
        ]);

        if ($validator->fails()) {
            return make_validation_error_response($validator->getMessageBag());
        }

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'subject' => $request->subject,
            'message' => $request->message
        ];

        Mail::send(['text' => 'Email.send_contact_form'], $data, function ($message) use ($data) {
            $message->to($data["email"], $data["name"]);
            $message->from(config('mail.contact_form_recipient_email'));
            $message->subject($data["subject"]);
        });

    } catch (Exception $exception) {
        return make_error_response($exception->getMessage());
    }

    return make_success_response("Email sent successfully.");
});

Route::post('/send-b2b-sale-form', function (Request $request) {
    try {
        $validator = Validator::make($request->all(), [
            'country_name' => ['required'],
            'name' => ['required'],
            'product_name' => ['required'],
            'product_code' => ['nullable'],
            'product_quantity' => ['required'],
            'contact_number' => ['required'],
            'email_address' => ['required', 'email'],
        ]);

        if ($validator->fails()) {
            return make_validation_error_response($validator->getMessageBag());
        }

        $data = [
            'country_name' => $request->country_name,
            'name' => $request->name,
            'product_name' => $request->product_name,
            'product_code' => $request->product_code,
            'product_quantity' => $request->product_quantity,
            'contact_number' => $request->contact_number,
            'email_address' => $request->email_address
        ];

        B2B::create([
            'country_name' => $request->country_name,
            'name' => $request->name,
            'product_name' => $request->product_name,
            'product_code' => $request->product_code,
            'product_quantity' => $request->product_quantity,
            'contact_number' => $request->contact_number,
            'email_address' => $request->email_address,
            'status' => B2B::STATUS_PENDING,
        ]);

        Mail::send(['text' => 'Email.send_b2b_sale_form'], $data, function ($message) use ($data) {
            $message->to($data["email_address"], $data["name"]);
            $message->from(config('mail.contact_form_recipient_email'));
            $message->subject("IFAD ECOM: B2B Sale Request");
        });

    } catch (Exception $exception) {
        return make_error_response($exception->getMessage());
    }

    return make_success_response("Mail sent successfully.");
});
