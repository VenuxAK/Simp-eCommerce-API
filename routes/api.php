<?php

use Illuminate\Support\Facades\Route;

/*
 * ┌──────────────────────────────────────────────────────────────────┐
 * │  SimpCommerce API — Master Route File                          │
 * │                                                                │
 * │  Every module registers its own routes in routes/modules/ and   │
 * │  this file delegates to them with the correct middleware groups.│
 * │                                                                │
 * │  Middleware inheritance:                                        │
 * │    • Public       — no auth (login, register)                   │
 * │    • Customer     — auth:customer (Sanctum, Customer guard)     │
 * │    • Staff        — auth:sanctum (Sanctum, User guard)          │
 * │    • Admin        — auth:sanctum + admin middleware             │
 * └──────────────────────────────────────────────────────────────────┘
 */

// ── 1. Public — no authentication required ───────────────────────
require __DIR__ . '/modules/auth.php';

// ── 2. Storefront — public, scoped by X-Store header ────────────
Route::middleware(['store', 'throttle:60,1'])->prefix('storefront')->group(function () {
    require __DIR__ . '/modules/storefront.php';
});

// ── 3. Customer portal — Sanctum with Customer guard ────────────
Route::middleware(['auth:customer', 'throttle:60,1'])->group(function () {
    require __DIR__ . '/modules/customer-portal.php';
});

// ── 3. Staff dashboard — Sanctum with User guard ────────────────
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    require __DIR__ . '/modules/identity.php';
    require __DIR__ . '/modules/catalog.php';
    require __DIR__ . '/modules/sales.php';
    require __DIR__ . '/modules/customer.php';
    require __DIR__ . '/modules/report.php';
    require __DIR__ . '/modules/promotion.php';
    require __DIR__ . '/modules/supplier.php';
    require __DIR__ . '/modules/cash.php';
    require __DIR__ . '/modules/inventory.php';
    require __DIR__ . '/modules/system.php';
    require __DIR__ . '/modules/audit.php';
    require __DIR__ . '/modules/store.php';
});
