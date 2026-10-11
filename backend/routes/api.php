<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\SalesAppController;
use App\Http\Controllers\Api\SettlementController;
use App\Http\Controllers\Api\VisitController;
use Illuminate\Support\Facades\Route;

// API untuk aplikasi Flutter "Oasis Sales" (motoris + SFA).
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/today', [SalesAppController::class, 'today']);
    Route::get('/products', [SalesAppController::class, 'products']);
    Route::get('/outlets/{outlet}', [SalesAppController::class, 'outlet']);

    Route::post('/visits', [VisitController::class, 'checkin']);
    Route::post('/visits/{visit}/stok-outlet', [VisitController::class, 'stokOutlet']);
    Route::post('/visits/{visit}/tutup', [VisitController::class, 'tutup']);

    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders/canvas', [OrderController::class, 'canvas']);
    Route::post('/orders/taking', [OrderController::class, 'takingOrder']);

    Route::get('/setoran', [SettlementController::class, 'show']);
    Route::post('/setoran', [SettlementController::class, 'store']);
});
