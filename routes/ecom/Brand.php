<?php

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
Route::get('/brands', function (Request $request) {

    $brands = [
        [
            'id' => 1,
            'title' => "Brand 1",
            'image' => "https://w7.pngwing.com/pngs/275/961/png-transparent-chanel-logo-brand-gucci-logo-text-trademark-fashion.png",
        ],
        [
            'id' => 2,
            'title' => "Brand 2",
            'image' => "https://w7.pngwing.com/pngs/732/34/png-transparent-logo-amazon-com-brand-flipkart-others-text-orange-logo-thumbnail.png",
        ],
        [
            'id' => 3,
            'title' => "Brand 2",
            'image' => "https://www.nicepng.com/png/detail/28-283120_logos-of-different-brands.png",
        ],
        [
            'id' => 4,
            'title' => "Brand 2",
            'image' => "https://w7.pngwing.com/pngs/732/34/png-transparent-logo-amazon-com-brand-flipkart-others-text-orange-logo-thumbnail.png",
        ],
        [
            'id' => 1,
            'title' => "Brand 1",
            'image' => "https://e7.pngegg.com/pngimages/64/316/png-clipart-logo-brand-lacoste-clothing-crocodile-crocodile-animals-text.png",
        ],
        [
            'id' => 2,
            'title' => "Brand 2",
            'image' => "https://w7.pngwing.com/pngs/732/34/png-transparent-logo-amazon-com-brand-flipkart-others-text-orange-logo-thumbnail.png",
        ],
        [
            'id' => 3,
            'title' => "Brand 2",
            'image' => "https://w7.pngwing.com/pngs/732/34/png-transparent-logo-amazon-com-brand-flipkart-others-text-orange-logo-thumbnail.png",
        ],
        [
            'id' => 4,
            'title' => "Brand 2",
            'image' => "https://www.pngitem.com/pimgs/m/249-2491766_sports-brand-logo-png-transparent-png.png",
        ],
    ];

    try {
        return $brands;
    } catch (Exception $exception) {
        return make_error_response($exception->getMessage());
    }
});

