<?php

use App\Events\MessageSent;
use App\Http\Controllers\admin\AuthController;
use App\Http\Controllers\admin\BrandController;
use App\Http\Controllers\admin\CategoryController;
use App\Http\Controllers\admin\OrderController as AdminOrderController;
use App\Http\Controllers\admin\ProductController;
use App\Http\Controllers\admin\ShippingController;
use App\Http\Controllers\admin\SizeController;
use App\Http\Controllers\admin\TempImageController;
use App\Http\Controllers\front\ChatController as FrontChatController;
use App\Http\Controllers\front\AccountController;
use App\Http\Controllers\front\OrderController;
use App\Http\Controllers\front\ProductController as FrontProductController;
use App\Http\Controllers\front\ShippingController as FrontShippingController;
use App\Http\Controllers\front\StripeController;
use App\Http\Middleware\CorsMiddleware;
use App\Models\Size;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\ChatMessage;

Route::post('/admin/login',[AuthController::class, 'authenticate']);
Route::get('get-latest-products',[FrontProductController::class, 'latestProducts']);
Route::get('get-featured-products',[FrontProductController::class, 'featuredProducts']);
Route::get('get-categories',[FrontProductController::class, 'getCategories']);
Route::get('get-brands',[FrontProductController::class, 'getBrands']);
Route::get('get-products',[FrontProductController::class, 'getProducts']);
Route::get('get-product/{id}',[FrontProductController::class, 'getProduct']);
Route::post('register',[AccountController::class,'register']);
Route::post('login',[AccountController::class,'authenticate']);
Route::get('get-shipping-front',[FrontShippingController::class, 'getShipping']);


Route::group(['middleware' => ['auth:sanctum', 'checkUserRole']], function(){
    Route::post('save-order',[OrderController::class,'saveOrder']);
    Route::get('get-order-details/{id}',[AccountController::class,'getOrderDetails']);
    Route::get('get-orders',[AccountController::class,'getOrders']);
    Route::post('update-profile',[AccountController::class,'updateProfile']);
    Route::get('get-profile-details',[AccountController::class,'getAccountDetails']);
    Route::post('/create-checkout-session', [StripeController::class, 'createCheckoutSession']);
    Route::post('make-payment', [StripeController::class, 'makePayment']);
    Route::post('/save-order-stripe', [StripeController::class, 'saveOrderFromStripe']);

});

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::group(['middleware' => ['auth:sanctum', 'checkAdminRole']], function(){

    Route::resource('categories', CategoryController::class);
    Route::resource('brands', BrandController::class);
    Route::get('sizes',[SizeController::class, 'index']);
    Route::resource('products', ProductController::class);
    Route::post('temp-images',[TempImageController::class, 'store']);
    Route::post('save-product-image',[ProductController::class, 'saveProductImage']);
    Route::get('change-default-image',[ProductController::class, 'updateDefaultImage']);
    Route::delete('delete-product-image/{id}',[ProductController::class, 'deleteProductImage']);
    Route::get('get-profile-details', [AccountController::class, 'getAccountDetails']);

    Route::get('orders',[AdminOrderController::class, 'index']);
    Route::get('orders/{id}',[AdminOrderController::class, 'show']);
    Route::post('update-order/{id}',[AdminOrderController::class, 'updateOrder']);

    Route::get('get-shipping',[ShippingController::class, 'getShipping']);
    Route::post('save-shipping',[ShippingController::class, 'updateShipping']);

});

    Route::middleware([CorsMiddleware::class])->group(function () {
        Route::get('/chat/messages', [FrontChatController::class, 'index']);
        Route::post('/chat/send', [FrontChatController::class, 'store']);
    });

    Route::get('/mongo-test', function () {
        ChatMessage::create([
            'user_id' => 1,
            'message' => 'Mesazh test nga Laravel në MongoDB!'
        ]);

        return 'Mesazhi u ruajt me sukses në MongoDB!';
    });

    Route::get('/send-test', function () {
        $msg = ChatMessage::create([
            'user_id' => 99,
            'message' => 'Test message nga browser'
        ]);

        broadcast(new MessageSent($msg))->toOthers();

        return 'Broadcast u dërgua';
    });

    Route::get('/chat/test-mongo', function () {
        $msg = \App\Models\ChatMessage::create([
            'user_id' => 1,
            'message' => 'Test nga MongoDB'
        ]);

        return response()->json($msg);
    });

?>
