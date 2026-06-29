<?php

use App\Modules\Customer\Http\Controllers\AddressController;
use App\Modules\Customer\Http\Controllers\CustomerAuthController;
use App\Modules\Customer\Http\Controllers\CustomerProfileController;
use App\Modules\ECommerce\Http\Controllers\CartController;
use App\Modules\ECommerce\Http\Controllers\CheckoutController;
use App\Modules\ECommerce\Http\Controllers\MyOrderController;
use App\Modules\ECommerce\Http\Controllers\WishlistController;
use App\Modules\Payment\Http\Controllers\PaymentIntentController;
use Illuminate\Support\Facades\Route;

// Profile.
Route::post('/customer/logout', [CustomerAuthController::class, 'logout']);
Route::get('/customer/me', [CustomerProfileController::class, 'show']);
Route::put('/customer/profile', [CustomerProfileController::class, 'update']);

// Address book.
Route::get('/addresses', [AddressController::class, 'index']);
Route::post('/addresses', [AddressController::class, 'store']);
Route::get('/addresses/{address}', [AddressController::class, 'show']);
Route::put('/addresses/{address}', [AddressController::class, 'update']);
Route::delete('/addresses/{address}', [AddressController::class, 'destroy']);
Route::put('/addresses/{address}/default', [AddressController::class, 'setDefault']);

// Cart.
Route::get('/cart', [CartController::class, 'index']);
Route::post('/cart', [CartController::class, 'add']);
Route::post('/cart/sync', [CartController::class, 'sync']);
Route::put('/cart/{cartItem}', [CartController::class, 'update']);
Route::delete('/cart/{cartItem}', [CartController::class, 'remove']);
Route::delete('/cart', [CartController::class, 'clear']);

// Checkout.
Route::post('/checkout', [CheckoutController::class, 'placeOrder'])
    ->middleware(['throttle:checkout', 'idempotent']);
Route::get('/checkout/validate', [CheckoutController::class, 'validateStock']);

// Payment intent (gateway payment before checkout).
Route::post('/checkout/payment-intent', [PaymentIntentController::class, 'create']);

// Order history.
Route::get('/my/orders', [MyOrderController::class, 'index']);
Route::get('/my/orders/{order}', [MyOrderController::class, 'show']);
Route::post('/my/orders/{order}/cancel', [MyOrderController::class, 'cancel']);

// Wishlist.
Route::get('/wishlist', [WishlistController::class, 'index']);
Route::post('/wishlist/toggle', [WishlistController::class, 'toggle']);
Route::delete('/wishlist/{id}', [WishlistController::class, 'destroy']);
Route::delete('/wishlist', [WishlistController::class, 'clear']);
