<?php

use App\Http\Resources\InventoryResource;
use App\Models\Inventory;
use App\Models\InventoryVariant;
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
            return InventoryResource::collection($query->paginate($request->get('limit', 15)));
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

        $inventory = Inventory::with(['product', 'inventoryVariants', 'inventoryImages'])->findOrFail($id);

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

        $query->when($keyword, function ($query) use ($keyword) {
            $query->whereHas('product', function ($query) use ($keyword) {
                $query->where('title', 'LIKE', "%" . $keyword . "%");
            });
        });

        $query->when($request->limit, function ($query) use ($request) {
            $query->limit($request->limit);
        });

        return $query->paginate();
    } catch (Exception $exception) {
        return make_error_response($exception->getMessage());
    }
});


/**
 *
 */
Route::post('/inventories/{inventoryId}/inventory-variants', function (Request $request, $inventoryId) {
    try {

        if (!$request->filled('inventory_variant_ids')) {
            throw new Exception('The given data was invalid!');
        }

        $inventoryVariantIds = $request->input('inventory_variant_ids', []);

        $inventoryVariation = InventoryVariant::with('inventory')
            ->whereIn('id', $inventoryVariantIds)
            ->where('inventory_id', '!=', $inventoryId)
            ->first();

        return $inventoryVariation;
    } catch (Exception $exception) {
        return make_error_response($exception->getMessage());
    }
});

/**
 *
 */
Route::get('/inventories/products/{productId}/variations/options', function ($productId) {
    try {
        $inventoryIds = Inventory::where('product_id', $productId)->get()->pluck('id');

        $inventoryVariants = InventoryVariant::with('variant', 'variantOption')->whereIn('inventory_id', $inventoryIds)->get();

        $variantOptions = [];
        foreach ($inventoryVariants as $inventoryVariant) {
            if ($inventoryVariant->variant && $inventoryVariant->variantOption) {
                $variantOptions[$inventoryVariant->variant->name][] = [
                    'inventory_variant_id' => $inventoryVariant->id,
                    'variant_option_name' => $inventoryVariant->variantOption->name
                ];
            }
        }

        return $variantOptions;

    } catch (Exception $exception) {
        return make_error_response($exception->getMessage());
    }
});
