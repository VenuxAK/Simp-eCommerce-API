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
 * │    • Stateful     — session cookie (for login)                  │
 * │    • Customer     — stateful + auth:customer guard              │
 * │    • Staff        — auth:sanctum (cookie or Bearer via stateful)│
 * │    • Admin        — staff + admin middleware                    │
 * └──────────────────────────────────────────────────────────────────┘
 */

// ── 1. Public — no authentication required ───────────────────────
require __DIR__ . '/modules/auth.php';

// ── 2. Customer portal — stateful session + Customer guard ──────
Route::middleware(['stateful', 'auth:customer', 'throttle:60,1'])->group(function () {
    require __DIR__ . '/modules/customer-portal.php';
});

// ── 3. Staff dashboard — stateful session + User guard ──────────
// Stateful enables cookie auth; Bearer tokens also work via Sanctum fallback.
Route::middleware(['stateful', 'auth:sanctum', 'throttle:60,1'])->group(function () {
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
