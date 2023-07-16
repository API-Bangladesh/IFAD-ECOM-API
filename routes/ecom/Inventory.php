<?php

use App\Http\Resources\InventoryResource;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

/**
 *
 */
Route::get('/inventories', function (Request $request) {
    try {
        $query = Inventory::query();
        $query->with(['product', 'inventoryVariants', 'inventoryImages']);
        $query->groupBy('product_id');

        $query->when($request->order_column && $request->order_by, function ($q) use ($request) {
            $q->orderBy($request->order_column, $request->order_by);
        });

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
Route::get('/inventories/discounted', function (Request $request) {
    try {
        $query = Inventory::query();
        $query->with(['product', 'inventoryVariants', 'inventoryImages']);
        $query->whereDate('offer_start', '<=', date('Y-m-d'));
        $query->whereDate('offer_end', '>=', date('Y-m-d'));
        $query->groupBy('product_id');

        $query->when($request->order_column && $request->order_by, function ($q) use ($request) {
            $q->orderBy($request->order_column, $request->order_by);
        });

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
Route::get('/inventories/categories/{categoryId}', function (Request $request, $categoryId) {
    try {
        $query = Inventory::query();
        $query->with(['product', 'inventoryVariants', 'inventoryImages']);
        $query->whereHas('product', function ($query) use ($categoryId) {
            $query->where('category_id', $categoryId);
        });
        $query->groupBy('product_id');

        $query->when($request->order_column && $request->order_by, function ($q) use ($request) {
            $q->orderBy($request->order_column, $request->order_by);
        });

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
Route::get('/inventories/subCategories/{subCategoryId}', function (Request $request, $subCategoryId) {
    try {
        $query = Inventory::query();
        $query->with(['product', 'inventoryVariants', 'inventoryImages']);
        $query->whereHas('product', function ($query) use ($subCategoryId) {
            $query->where('sub_category_id', $subCategoryId);
        });
        $query->groupBy('product_id');

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
Route::get('/inventories/{id}/show', function ($id) {
    try {

        $inventory =  Inventory::with(['product', 'inventoryVariants', 'inventoryImages'])->findOrFail($id);

        return new InventoryResource($inventory);
    } catch (Exception $exception) {
        return make_error_response($exception->getMessage());
    }
});

/**
 *
 */
Route::get('/inventories/search', function (Request $request) {
    try {
        $keyword = $request->keyword;

        $query = Inventory::query();
        $query->groupBy('product_id');
        $query->with(['product', 'inventoryVariants', 'inventoryImages']);
        $query->whereHas('product', function ($query) use ($keyword) {
            $query->where('title', 'LIKE', "%" . $keyword . "%");
        });

        $query->when($request->limit, function ($q) use ($request) {
            $q->limit($request->limit);
        });

        return $query->paginate();
    } catch (Exception $exception) {
        return make_error_response($exception->getMessage());
    }
});
