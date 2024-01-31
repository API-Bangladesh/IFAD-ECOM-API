<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class CouponUserGroup extends Model
{
    use HasFactory;
    protected $table = 'coupon_user_groups';
    public $timestamps = false;
}
