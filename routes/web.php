<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/
//product Category List
use Illuminate\Support\Facades\Session;

$router->get('/categories', 'ProductController@allCategory');
//product Category id wies subcategory List /{1}
$router->get('/categories/{id}', 'ProductController@allSubCategory');
//product Category with subcategory List
$router->get('/categories_with_subcategory', 'ProductController@allCategroyWithSubCategorylist');
//category id wise prodcut
$router->get('/categories_id_wise_product/{id}', 'ProductController@CategoryidWiseProduct');
//sub category id wise prodcut
$router->get('/subcategories_id_wise_product/{id}', 'ProductController@SubCategoryidWiseProduct');
//prodcut list
$router->get('/product-list', 'ProductController@productList');
//prodcut list
$router->get('/product-list/{id}', 'ProductController@productIdWiseDetails');


$router->get('/products', 'ProductController@allProduct');

$router->get('/products/{id}', 'ProductController@singleProduct');

//document list
$router->get('/documents', 'DocumentController@allDocument');

//settings list
$router->get('/settings', 'SettingsController@allSettings');

//test product
//$router->get('/testproduct', 'ProductController@testProduct');

//Contact
$router->get('/contacts', 'ContactController@allContact');

//
$router->get('/picture-categories', 'GalleryCategoryController@galleryPictureCategory');
//
$router->get('/video-categories', 'GalleryCategoryController@galleryVideoCategory');
//picture
$router->get('/pictures', 'PictureController@allPictures');
//video
$router->get('/videos', 'VideoController@allVideos');

//blog
$router->get('/blogs', 'BlogController@allBlog');

//id wise blog details
$router->get('/blogs/{id}', 'BlogController@blogDetails');


$router->get('/cotent-items', 'ContentItemController@contentItemDetails');

$router->get('/content-module/{id}', 'ContentModuleController@contentModule');

// mail department
$router->get('/mail-departments', 'MailSettingsController@allMailDepartment');

//website menus
$router->get('/menus', 'ModuleController@menus');

//send mail
//$router->get('/send-email', 'MailController@sendEmail');

$router->post('/send-email', 'MailController@sendEmail');
//product Search
$router->get('/search/{name}', 'SearchController@Search');

/*Ecom*/
$router->group(['prefix' => 'ecom'], function () use ($router) {
    $router->get('/', function (){
        return 'IFAD Ecom Ready';
    });

    include_once 'ecom/Auth.php';
    include_once 'ecom/Wishlist.php';
    include_once 'ecom/Combo.php';
    include_once 'ecom/ComboCategory.php';
    include_once 'ecom/Customer.php';
    include_once 'ecom/Address.php';
    include_once 'ecom/Category.php';
    include_once 'ecom/SubCategory.php';
    include_once 'ecom/Inventory.php';
    include_once 'ecom/Order.php';
    include_once 'ecom/PaymentMethod.php';
    include_once 'ecom/Common.php';
    include_once 'ecom/Review.php';
    include_once 'ecom/Banner.php';
    include_once 'ecom/Brand.php';
});
