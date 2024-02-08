<?php

namespace App\Http\Controllers;
use App\Models\ConditionalDiscount;
use Illuminate\Http\Request;

class ConditionalDiscountController extends Controller
{
    public function index(){
        $ConditionalDiscount = ConditionalDiscount::all();
        return $ConditionalDiscount;
    }

}
