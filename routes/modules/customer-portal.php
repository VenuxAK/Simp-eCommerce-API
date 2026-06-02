<?php

use Illuminate\Support\Facades\Route;

/*
 * Customer portal — authenticated via Sanctum with Customer guard.
 * Manages the customer's own profile, address book, and orders.
 */

// Profile.
Route::post('/customer/logout', [App\Modules\Customer\Http\Controllers\CustomerAuthController::class, 'logout']);
Route::get('/customer/me', [App\Modules\Customer\Http\Controllers\CustomerProfileController::class, 'show']);
Route::put('/customer/profile', [App\Modules\Customer\Http\Controllers\CustomerProfileController::class, 'update']);

// Address book.
Route::get('/addresses', [App\Modules\Customer\Http\Controllers\AddressController::class, 'index']);
Route::post('/addresses', [App\Modules\Customer\Http\Controllers\AddressController::class, 'store']);
Route::get('/addresses/{address}', [App\Modules\Customer\Http\Controllers\AddressController::class, 'show']);
Route::put('/addresses/{address}', [App\Modules\Customer\Http\Controllers\AddressController::class, 'update']);
Route::delete('/addresses/{address}', [App\Modules\Customer\Http\Controllers\AddressController::class, 'destroy']);
Route::put('/addresses/{address}/default', [App\Modules\Customer\Http\Controllers\AddressController::class, 'setDefault']);

// Cart.
Route::get('/cart', [App\Modules\ECommerce\Http\Controllers\CartController::class, 'index']);
Route::post('/cart', [App\Modules\ECommerce\Http\Controllers\CartController::class, 'add']);
Route::put('/cart/{cartItem}', [App\Modules\ECommerce\Http\Controllers\CartController::class, 'update']);
Route::delete('/cart/{cartItem}', [App\Modules\ECommerce\Http\Controllers\CartController::class, 'remove']);
Route::delete('/cart', [App\Modules\ECommerce\Http\Controllers\CartController::class, 'clear']);

// Checkout.
Route::post('/checkout', [App\Modules\ECommerce\Http\Controllers\CheckoutController::class, 'placeOrder']);
Route::get('/checkout/validate', [App\Modules\ECommerce\Http\Controllers\CheckoutController::class, 'validateStock']);

// Order history.
Route::get('/my/orders', [App\Modules\ECommerce\Http\Controllers\MyOrderController::class, 'index']);
Route::get('/my/orders/{order}', [App\Modules\ECommerce\Http\Controllers\MyOrderController::class, 'show']);
Route::post('/my/orders/{order}/cancel', [App\Modules\ECommerce\Http\Controllers\MyOrderController::class, 'cancel']);

// Wishlist.
Route::get('/wishlist', [App\Modules\ECommerce\Http\Controllers\WishlistController::class, 'index']);
Route::post('/wishlist/toggle', [App\Modules\ECommerce\Http\Controllers\WishlistController::class, 'toggle']);
Route::delete('/wishlist/{id}', [App\Modules\ECommerce\Http\Controllers\WishlistController::class, 'destroy']);
Route::delete('/wishlist', [App\Modules\ECommerce\Http\Controllers\WishlistController::class, 'clear']);
