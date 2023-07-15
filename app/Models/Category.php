<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\SubCategory;
use App\Models\Product;

/**
 * @method static paginate()
 * @method static findOrFail($id)
 * @method static get()
 */
class Category extends Model
{
    use HasFactory;
    protected $table = 'categories';
    public $timestamps = false;

    public function subCategories()
    {
        return $this->hasMany(SubCategory::class,'category_id', 'id')->where('status', 1);
    }

    public function product()
    {
      return $this->hasOne(Product::class);
    }

    public function subcategory()
    {
      return $this->belongsTo(SubCategory::class);
    }
}
