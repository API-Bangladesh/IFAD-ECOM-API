<?php

use App\Models\Division;
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


Route::get('/divisions', function (Request $request) {
    try {
        $query = Division::query();

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