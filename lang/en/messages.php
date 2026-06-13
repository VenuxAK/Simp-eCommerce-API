<?php

return [
    'auth' => [
        'failed' => 'These credentials do not match our records.',
        'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',
        'password_reset' => 'Password reset link has been sent to your email.',
        'password_reset_failed' => 'Password reset failed. The link may have expired.',
        'forgot_generic' => 'If the account exists and uses email login, a reset link has been sent.',
        'oauth_failed' => 'OAuth authentication failed.',
        'oauth_email_required' => 'Email is required from OAuth provider.',
    ],
    'validation' => [
        'required' => 'The :attribute field is required.',
        'email' => 'The :attribute must be a valid email address.',
        'min' => ['string' => 'The :attribute must be at least :min characters.'],
        'unique' => 'The :attribute has already been taken.',
        'password_policy' => 'Password must be at least 8 characters with uppercase, lowercase, and a digit.',
    ],
    'cart' => [
        'added' => 'Item added to cart.',
        'removed' => 'Item removed from cart.',
        'cleared' => 'Cart cleared.',
        'empty' => 'Your cart is empty.',
    ],
    'checkout' => [
        'cart_empty' => 'Cart is empty.',
        'invalid_address' => 'Invalid shipping address.',
        'insufficient_stock' => 'Insufficient stock for :sku. Available: :available.',
    ],
    'orders' => [
        'created' => 'Order placed successfully.',
        'cancelled' => 'Order cancelled successfully.',
        'not_found' => 'Order not found.',
        'cancel_not_allowed' => 'Only orders in processing status can be cancelled.',
        'cannot_return' => 'Order cannot be returned.',
    ],
    'address' => [
        'created' => 'Address created.',
        'updated' => 'Address updated.',
        'deleted' => 'Address deleted.',
        'default_set' => 'Default address updated.',
    ],
    'catalog' => [
        'brand_delete_blocked' => 'Cannot delete brand: :count product(s) are linked to it.',
        'category_delete_blocked' => 'Cannot delete category: :count product(s) are linked to it.',
        'category_has_children' => 'Cannot delete category: it has :count sub-categor(ies).',
        'variant_not_found' => 'Variant not found for the given SKU.',
    ],
    'store' => [
        'cannot_delete_main' => 'Cannot delete the main store.',
    ],
    'supplier' => [
        'delete_blocked' => 'Cannot delete supplier with :count product(s).',
    ],
    'cash' => [
        'session_already_open' => 'A cash session is already open for this store.',
        'no_open_session' => 'No open cash session for this store.',
    ],
    'backup' => [
        'not_found' => 'Backup not found.',
    ],
    'order_status' => [
        'invalid_transition' => 'Cannot transition from :current to :new.',
    ],
];
