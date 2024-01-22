<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function index(Request $request)
    {
        // $validCoupon = Coupon::where('coupon_code', $request->coupon_code)
        // ->where('coupon_exp_date', '>', now())  // Assuming 'coupon_exp_date' is a datetime column
        // ->where('limit_per_coupon', '>', 0)
        // ->first();

        $validCoupon = Coupon::where('coupon_code', $request->coupon_code)
        ->where('coupon_exp_date', '>', now()) // Assuming 'coupon_exp_date' is a datetime column
        ->where('limit_per_coupon', '>', 0)
        ->first();



        $subTotal = $request->sub_total;

        // Check if the coupon has a minimum spend requirement and if the subtotal meets this requirement
        if (($validCoupon->coupon_min_spend !== null && $subTotal < $validCoupon->coupon_min_spend) ||
            ($validCoupon->coupon_max_spend !== null && $subTotal > $validCoupon->coupon_max_spend)) {

            $message = '';
            if ($validCoupon->coupon_min_spend !== null && $subTotal < $validCoupon->coupon_min_spend) {
                $message = 'Cart does not meet the minimum spend requirement for this coupon.';
            } elseif ($validCoupon->coupon_max_spend !== null && $subTotal > $validCoupon->coupon_max_spend) {
                $message = 'Cart exceeds the maximum spend limit for this coupon.';
            }

            return response()->json([
                'code' => 400,
                'message' => $message,
            ], 400);
        }

        if (!$validCoupon) {
            return response()->json([
                'code' => 400,
                'message' => 'Invalid Coupon',
            ], 400);
        }else{
            $sub_total=$request->sub_total;
            $previous_subtotal=$request->sub_total;

            if($validCoupon->coupon_discount_type == 'fixed_amount_discount'){
                 $shipping_charge = $request->shipping_charge;
                if($validCoupon->is_free_delivery == 1){
                    $shipping_charge= 0;
                }else{
                    $shipping_charge=$request->shipping_charge;
                }
                $discountCouponAmount=$validCoupon->coupon_amount;
                //return $shipping_charge;
                $subtotalAmount=$sub_total-$validCoupon->coupon_amount;

                $grand_total = $sub_total-$validCoupon->coupon_amount+$shipping_charge;

                $TotalCoupon=$validCoupon->limit_per_coupon -1;



                return response()->json([
                    'coupon_discount_type' => 'Fixed Amount Discount',
                    'discount_coupon_amount' => $discountCouponAmount,
                    'previous_subtotal' => $previous_subtotal,
                    'sub_total' => $subtotalAmount,
                    'shipping_charge' => $shipping_charge,
                    'grand_total' => $grand_total,
                ], 200);

           }

            if($validCoupon->coupon_discount_type == 'percentage_discount'){

                if(($validCoupon->product_id == null || '') && ($validCoupon->exclude_id == null || '') && ($validCoupon->category_id == null || '') && ($validCoupon->exclude_category_id == null || '')) {
                    $cart = $request['cart'];

                    // Apply discount only to non-combo items
                    foreach ($cart as &$item) {
                        if ($item['type'] === 'combo') {
                            continue; // Skip combo items
                        }
                        // Apply the percentage discount
                        $discount_amount = $item['total'] * $validCoupon->coupon_amount / 100;
                        $item['total'] -= $discount_amount;
                    }

                    // Calculate the new subtotal after discount
                    $sub_total = array_sum(array_column($cart, 'total'));

                    // Shipping charge logic
                    $shipping_charge = $request->shipping_charge;
                    if($validCoupon->is_free_delivery == 1){
                        $shipping_charge = 0;
                    }

                    // Calculate the grand total
                    $grand_total = $sub_total + $shipping_charge;

                    // Prepare the response
                    return response()->json([
                        'coupon_discount_type' => 'Percentage wise Discount',
                        'discount_coupon_amount' => $validCoupon->coupon_amount . '%',
                        'previous_subtotal' => $request->sub_total,
                        'sub_total' => $sub_total,
                        'shipping_charge' => $shipping_charge,
                        'grand_total' => $grand_total,
                    ], 200);
                }


             if (($validCoupon->product_id != null || '') ||
                ($validCoupon->exclude_id != null || '') ||
                ($validCoupon->category_id != null || '') ||
                ($validCoupon->exclude_category_id != null || '')) {
                $include_ids = json_decode($validCoupon->product_id, true) ?? [];
                $exclude_ids = json_decode($validCoupon->exclude_id, true) ?? [];
                $include_category_ids = json_decode($validCoupon->category_id, true) ?? [];
                $exclude_category_ids = json_decode($validCoupon->exclude_category_id, true) ?? [];
                $cart = $request['cart'];

                $allCombo = true;
                foreach ($cart as $item) {
                    if ($item['type'] !== 'combo') {
                        $allCombo = false;
                        break;
                    }
                }
                if ($allCombo) {
                // Logic to handle scenario where all items are of type 'combo'
                // For example, return a custom response or apply a different discount rule
                    return response()->json([
                        'message' => 'Discount not applicable on combo items only.',
                        // other response data as needed
                    ], 406);
                } else {
                    // Existing logic for applying discounts
                     foreach ($cart as &$item) {
                        if ($item['type'] === 'combo') {
                            continue; // Skip combo items
                        }
                        $applyDiscount = false;

                        // Exclude by category first
                        if (!empty($exclude_category_ids) && in_array($item['category_id'], $exclude_category_ids)) {
                            $applyDiscount = false;
                        }
                        // Then check for inclusion by product ID
                        else if (!empty($include_ids) && in_array((string)$item['inventory_id'], $include_ids)) {
                            $applyDiscount = true;
                        }
                        // If no product_id conditions, check category_id for inclusion
                        else if (empty($include_ids) && !empty($include_category_ids) && in_array($item['category_id'], $include_category_ids)) {
                            $applyDiscount = true;
                        }

                        // Apply discount
                        if ($applyDiscount && !(in_array((string)$item['inventory_id'], $exclude_ids))) {
                            $discount_amount = $item['total'] * $validCoupon->coupon_amount / 100;
                            $item['total'] -= $discount_amount;
                        }
                    }

                    // ... rest of the code to calculate and return the response
                }


                $sub_total = array_sum(array_column($cart, 'total'));
                if($validCoupon->is_free_delivery == 1){
                                    $shipping_charge= 0;
                                }else{
                                    $shipping_charge=$request->shipping_charge;
                                }

                                $discountCouponAmount=$validCoupon->coupon_amount;
                                //return $shipping_charge;
                                $grand_total = $sub_total+$shipping_charge;

                                $TotalCoupon=$validCoupon->limit_per_coupon -1;


                                return response()->json([
                                    'coupon_discount_type' => 'Percentage wise Discount',
                                    'discount_coupon_amount' => $discountCouponAmount.'%',
                                    'previous_subtotal' => $previous_subtotal,
                                    'sub_total' => $sub_total,
                                    'shipping_charge' => $shipping_charge,
                                    'grand_total' => $grand_total,
                                ], 200);
            }

            }

            if($validCoupon->coupon_discount_type == 'fixed_product_discount'){



                if(($validCoupon->product_id != null || '') || ($validCoupon->exclude_id != null || '')){

                    $include_ids = json_decode($validCoupon->product_id, true) ?? [];  // Default to empty array if null
                    $exclude_ids = json_decode($validCoupon->exclude_id, true) ?? [];

                    $cart = $request['cart'];

                    $allCombo = true;
                    foreach ($cart as $item) {
                        if ($item['type'] !== 'combo') {
                            $allCombo = false;
                            break;
                        }
                    }
                    if ($allCombo) {
                    // Logic to handle scenario where all items are of type 'combo'
                    // For example, return a custom response or apply a different discount rule
                        return response()->json([
                            'message' => 'Discount not applicable on combo items only.',
                            // other response data as needed
                        ], 200);
                    }else{
                         foreach ($cart as &$item) {
                        if ($item['type'] === 'combo' ) {
                            continue; // Skip this item, treat it as excluded
                        }
                        // Ensure include_ids is not empty before applying discounts
                        if (!empty($include_ids) && in_array((string)$item['inventory_id'], $include_ids) &&!empty($include_ids) && in_array((string)$item['inventory_id'], $include_ids) &&
                            !(in_array((string)$item['inventory_id'], $exclude_ids))) {  // Check exclude_ids only if not empty
                            $discount_amount = $validCoupon->coupon_amount;
                            $item['total'] -= $discount_amount;
                            // Apply any additional discount logic here, if needed
                        }
                    }

                    $sub_total = array_sum(array_column($cart, 'total'));

                    if($validCoupon->is_free_delivery == 1){
                        $shipping_charge= 0;
                    }else{
                        $shipping_charge=$request->shipping_charge;
                    }


                    $discountCouponAmount=$validCoupon->coupon_amount;
                    //return $shipping_charge;

                    $grand_total = $sub_total+$shipping_charge;

                    $TotalCoupon=$validCoupon->limit_per_coupon -1;

                    Coupon::where('id',$validCoupon->id)->update(['limit_per_coupon'=>$TotalCoupon]);

                    return response()->json([
                        'coupon_discount_type' => 'Fixed Product Discount',
                        'discount_coupon_amount' => $discountCouponAmount.'Taka',
                        'previous_subtotal' => $previous_subtotal,
                        'sub_total' => $sub_total,
                        'shipping_charge' => $shipping_charge,
                        'grand_total' => $grand_total,
                    ], 200);

                    }

                   }else{
                    return response()->json([
                        'coupon_discount_type' => 'Fixed Product Discount',
                        'Message' => 'Coupon Not Applicable',
                    ],406 );

                }

            }
        }

    }
}
