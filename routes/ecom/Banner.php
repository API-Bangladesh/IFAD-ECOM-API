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
Route::get('/banners', function (Request $request) {

    $banners = [
        [
            'id' => 1,
            'title' => "Banner 1",
            'image' => "https://img.freepik.com/premium-psd/sport-shoes-sale-social-media-post-facebook-banner-web-banner-template_70055-842.jpg",
        ],
        [
            'id' => 2,
            'title' => "Banner 2",
            'image' => "https://png.pngtree.com/background/20210715/original/pngtree-black-elegant-high-end-product-banner-base-map-background-picture-image_1303664.jpg",
        ]
    ];

    try {
        return $banners;
    } catch (Exception $exception) {
        return make_error_response($exception->getMessage());
    }
});

