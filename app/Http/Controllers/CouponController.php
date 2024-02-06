<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\CouponUserGroup;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function index(Request $request)
    {


        $validCoupon = Coupon::where('coupon_code', $request->coupon_code)
        ->where('coupon_exp_date', '>', now()) // Assuming 'coupon_exp_date' is a datetime column
        ->where('limit_per_coupon', '>', 0)
        ->first();

        $subTotal = $request->sub_total;

        $currentCustomer = $request->customer_id; // Logged-in user's customer ID
        $include_group_ids = json_decode($validCoupon->customer_id, true) ?? []; // User group IDs allowed by the coupon
        $individual_customer_ids = json_decode($validCoupon->include_customer_id, true) ?? [];

        // Directly included customer IDs

        // Fetch customer IDs for each user group mentioned in the coupon
        if(($individual_customer_ids != null || '' || [] ) || ($include_group_ids != null || '' || [] )){

                $group_customer_ids = [];
                foreach ($include_group_ids as $group_id) {
                // Assuming you have a model CouponUserGroup where 'group_id' and 'customer_ids' are columns
                $group = CouponUserGroup::where('id', $group_id)->first();


                if ($group) {
                    $customers_in_group = json_decode($group->customer_id, true) ?? [];

                    $group_customer_ids = array_merge($group_customer_ids, $customers_in_group);


                }

            }


            $allowed_customer_ids = array_merge($individual_customer_ids, $group_customer_ids);
            $allowed_customer_ids = array_unique($allowed_customer_ids);
            // return $allowed_customer_ids;


            // Check if the current customer is allowed
            if (!in_array($currentCustomer, $allowed_customer_ids)) {

                return response()->json([
                            'message' => 'Discount not applicable on this user.',
                            // other response data as needed
                        ], 406);
            }


        }



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

            if ($validCoupon->coupon_discount_type == 'fixed_amount_discount') {
                    $discountApplied = false;

                    // return $currentCustomer;

                    $cart = $request['cart'];

                    $include_ids = json_decode($validCoupon->product_id, true) ?? [];
                    $exclude_ids = json_decode($validCoupon->exclude_id, true) ?? [];
                    $include_category_ids = json_decode($validCoupon->category_id, true) ?? [];
                    $exclude_category_ids = json_decode($validCoupon->exclude_category_id, true) ?? [];

                    $coupon_combo_ids = json_decode($validCoupon->combo_id, true) ?? [];
                    $coupon_exclude_combo_ids = json_decode($validCoupon->exclude_combo_id, true) ?? [];


                    $applyToAll = empty($include_ids) && empty($exclude_ids) && empty($include_category_ids) && empty($exclude_category_ids);

                    foreach ($cart as &$item) {
                        $applyDiscount = false;

                        if ($item['type'] === 'combo') {
                            // Check if the combo ID is either included or excluded
                            $isComboIncluded = in_array($item['combo_id'], $coupon_combo_ids);
                            $isComboExcluded = in_array($item['combo_id'], $coupon_exclude_combo_ids);

                            if (!empty($coupon_combo_ids) && $isComboIncluded && !$isComboExcluded) {
                                // Apply discount to included combos that are not excluded
                                $applyDiscount = true;
                            } elseif (empty($coupon_combo_ids) && empty($coupon_exclude_combo_ids)) {
                                // If no specific combo inclusion or exclusion, treat as normal item
                                $applyDiscount = true;
                            }
                        } else {
                            // Non-combo item logic
                            $isExcluded = in_array((string)$item['inventory_id'], $exclude_ids) || in_array($item['category_id'], $exclude_category_ids);
                            $isIncluded = in_array((string)$item['inventory_id'], $include_ids) || in_array($item['category_id'], $include_category_ids);

                            $specificInclusion = !empty($include_category_ids) && in_array($item['category_id'], $include_category_ids) && in_array((string)$item['inventory_id'], $include_ids);
                            $specificExclusion = !empty($exclude_category_ids) && in_array($item['category_id'], $exclude_category_ids) && in_array((string)$item['inventory_id'], $exclude_ids);

                            if ($applyToAll && !$isExcluded) {
                                $applyDiscount = true;
                            } elseif (!$isExcluded) {
                                if ($isIncluded || $specificInclusion) {
                                    $applyDiscount = true;
                                } elseif (empty($include_ids) && empty($include_category_ids) && !$specificExclusion) {
                                    $applyDiscount = true;
                                }
                            }
                        }

                        // Apply discount
                        if ($applyDiscount) {
                            $item['total'] -= $validCoupon->coupon_amount;
                            $discountApplied = true;
                        }
                    }

                    if (!$discountApplied) {
                        return response()->json([
                            'message' => 'No applicable items for discount.',
                            // other response data as needed
                        ], 406);
                    }

                    $sub_total = array_sum(array_column($cart, 'total'));

                    // Shipping charge logic
                    $shipping_charge = $validCoupon->is_free_delivery == 1 ? 0 : $request->shipping_charge;

                    // Calculate the grand total
                    $grand_total = $sub_total + $shipping_charge;

                    // Prepare the response
                    return response()->json([
                        'coupon_discount_type' => 'Fixed_Amount_Discount',
                        'discount_coupon_amount' => $validCoupon->coupon_amount,
                        'previous_subtotal' => $request->sub_total,
                        'sub_total' => $sub_total,
                        'shipping_charge' => $shipping_charge,
                        'grand_total' => $grand_total,
                    ], 200);
        }


            if ($validCoupon->coupon_discount_type == 'percentage_discount') {
                        $discountApplied = false;

                        // return $currentCustomer;

                        $cart = $request['cart'];

                        $include_ids = json_decode($validCoupon->product_id, true) ?? [];
                        $exclude_ids = json_decode($validCoupon->exclude_id, true) ?? [];
                        $include_category_ids = json_decode($validCoupon->category_id, true) ?? [];
                        $exclude_category_ids = json_decode($validCoupon->exclude_category_id, true) ?? [];

                        $coupon_combo_ids = json_decode($validCoupon->combo_id, true) ?? [];
                        $coupon_exclude_combo_ids = json_decode($validCoupon->exclude_combo_id, true) ?? [];


                        $applyToAll = empty($include_ids) && empty($exclude_ids) && empty($include_category_ids) && empty($exclude_category_ids);

                        foreach ($cart as &$item) {
                            $applyDiscount = false;

                            if ($item['type'] === 'combo') {
                                // Check if the combo ID is either included or excluded
                                $isComboIncluded = in_array($item['combo_id'], $coupon_combo_ids);
                                $isComboExcluded = in_array($item['combo_id'], $coupon_exclude_combo_ids);

                                if (!empty($coupon_combo_ids) && $isComboIncluded && !$isComboExcluded) {
                                    // Apply discount to included combos that are not excluded
                                    $applyDiscount = true;
                                } elseif (empty($coupon_combo_ids) && empty($coupon_exclude_combo_ids)) {
                                    // If no specific combo inclusion or exclusion, treat as normal item
                                    $applyDiscount = true;
                                }
                            } else {
                                // Non-combo item logic
                                $isExcluded = in_array((string)$item['inventory_id'], $exclude_ids) || in_array($item['category_id'], $exclude_category_ids);
                                $isIncluded = in_array((string)$item['inventory_id'], $include_ids) || in_array($item['category_id'], $include_category_ids);

                                $specificInclusion = !empty($include_category_ids) && in_array($item['category_id'], $include_category_ids) && in_array((string)$item['inventory_id'], $include_ids);
                                $specificExclusion = !empty($exclude_category_ids) && in_array($item['category_id'], $exclude_category_ids) && in_array((string)$item['inventory_id'], $exclude_ids);

                                if ($applyToAll && !$isExcluded) {
                                    $applyDiscount = true;
                                } elseif (!$isExcluded) {
                                    if ($isIncluded || $specificInclusion) {
                                        $applyDiscount = true;
                                    } elseif (empty($include_ids) && empty($include_category_ids) && !$specificExclusion) {
                                        $applyDiscount = true;
                                    }
                                }
                            }

                            // Apply discount
                            if ($applyDiscount) {
                                $discount_amount = $item['total'] * $validCoupon->coupon_amount / 100;
                                $item['total'] -= $discount_amount;
                                $discountApplied = true;
                            }
                        }

                        if (!$discountApplied) {
                            return response()->json([
                                'message' => 'No applicable items for discount.',
                                // other response data as needed
                            ], 406);
                        }

                        $sub_total = array_sum(array_column($cart, 'total'));

                        // Shipping charge logic
                        $shipping_charge = $validCoupon->is_free_delivery == 1 ? 0 : $request->shipping_charge;

                        // Calculate the grand total
                        $grand_total = $sub_total + $shipping_charge;

                        // Prepare the response
                        return response()->json([
                                'coupon_discount_type' => 'Percentage_wise_Discount',
                                'discount_coupon_amount' => $validCoupon->coupon_amount . '%',
                                'previous_subtotal' => $request->sub_total,
                                'sub_total' => $sub_total,
                                'shipping_charge' => $shipping_charge,
                                'grand_total' => $grand_total,
                            ], 200);
            }

                if ($validCoupon->coupon_discount_type == 'fixed_product_discount') {
                    $discountApplied = false;

                    $cart = $request['cart'];

                    $include_ids = json_decode($validCoupon->product_id, true) ?? [];
                    $exclude_ids = json_decode($validCoupon->exclude_id, true) ?? [];
                    $include_category_ids = json_decode($validCoupon->category_id, true) ?? [];
                    $exclude_category_ids = json_decode($validCoupon->exclude_category_id, true) ?? [];

                    $coupon_combo_ids = json_decode($validCoupon->combo_id, true) ?? [];
                    $coupon_exclude_combo_ids = json_decode($validCoupon->exclude_combo_id, true) ?? [];

                    $applyToAll = empty($include_ids) && empty($exclude_ids) && empty($include_category_ids) && empty($exclude_category_ids);

                    foreach ($cart as &$item) {
                        $applyDiscount = false;

                        if ($item['type'] === 'combo') {
                            // Combo item logic
                            // Check if the combo ID is either included or excluded
                            $isComboIncluded = in_array($item['combo_id'], $coupon_combo_ids);
                            $isComboExcluded = in_array($item['combo_id'], $coupon_exclude_combo_ids);

                            if (!empty($coupon_combo_ids) && $isComboIncluded && !$isComboExcluded) {
                                // Apply discount to included combos that are not excluded
                                $applyDiscount = true;
                            } elseif (empty($coupon_combo_ids) && empty($coupon_exclude_combo_ids)) {
                                // If no specific combo inclusion or exclusion, treat as normal item
                                $applyDiscount = true;
                            }
                        } else {
                            // Non-combo item logic
                            $isExcluded = in_array((string)$item['inventory_id'], $exclude_ids) || in_array($item['category_id'], $exclude_category_ids);
                            $isIncluded = in_array((string)$item['inventory_id'], $include_ids) || in_array($item['category_id'], $include_category_ids);

                            $specificInclusion = !empty($include_category_ids) && in_array($item['category_id'], $include_category_ids) && in_array((string)$item['inventory_id'], $include_ids);
                            $specificExclusion = !empty($exclude_category_ids) && in_array($item['category_id'], $exclude_category_ids) && in_array((string)$item['inventory_id'], $exclude_ids);

                            if ($applyToAll && !$isExcluded) {
                                $applyDiscount = true;
                            } elseif (!$isExcluded) {
                                if ($isIncluded || $specificInclusion) {
                                    $applyDiscount = true;
                                } elseif (empty($include_ids) && empty($include_category_ids) && !$specificExclusion) {
                                    $applyDiscount = true;
                                }
                            }
                        }

                        // Apply discount to each item individually
                        if ($applyDiscount) {
                            $discount_amount = $validCoupon->coupon_amount;
                            $item['total'] -= $discount_amount;
                            $discountApplied = true;
                        }
                    }

                    if (!$discountApplied) {
                        return response()->json([
                            'message' => 'No applicable items for discount.',
                            // other response data as needed
                        ], 406);
                    }

                    $sub_total = array_sum(array_column($cart, 'total'));

                    // Shipping charge logic
                    $shipping_charge = $validCoupon->is_free_delivery == 1 ? 0 : $request->shipping_charge;

                    // Calculate the grand total
                    $grand_total = $sub_total + $shipping_charge;
                    // return $grand_total;
                    //$totalDiscount = strval($validCoupon->coupon_amount * count($cart));

                    // return  $totalDiscount;
                    // Prepare the response
                    return response()->json([
                        'coupon_discount_type' => 'Fixed_Product_Discount',
                        'discount_coupon_amount' => $validCoupon->coupon_amount,
                        'previous_subtotal' => $request->sub_total,
                        'sub_total' => $sub_total,
                        'shipping_charge' => $shipping_charge,
                        'grand_total' => $grand_total,
                    ], 200);
                }

        }

    }
}
