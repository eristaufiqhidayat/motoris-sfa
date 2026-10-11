<?php

use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\GalonController;
use App\Http\Controllers\Web\MuatController;
use App\Http\Controllers\Web\OpnameController;
use App\Http\Controllers\Web\OrderController;
use App\Http\Controllers\Web\PenerimaanController;
use App\Http\Controllers\Web\StokController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'form'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth', 'backoffice'])->group(function () {
    Route::get('/', [StokController::class, 'index'])->name('stok');
    Route::get('/galon', [GalonController::class, 'index'])->name('galon');

    Route::get('/penerimaan', [PenerimaanController::class, 'index'])->name('penerimaan');
    Route::post('/penerimaan/{receipt}/terima', [PenerimaanController::class, 'terima'])->name('penerimaan.terima');
    Route::post('/penerimaan/kirim-kosong', [PenerimaanController::class, 'kirimKosong'])->name('penerimaan.kirim-kosong');

    Route::get('/muat', [MuatController::class, 'index'])->name('muat');
    Route::post('/muat', [MuatController::class, 'store'])->name('muat.store');
    Route::post('/muat/{loading}/terima', [MuatController::class, 'terima'])->name('muat.terima');

    Route::get('/order', [OrderController::class, 'index'])->name('order');
    Route::post('/order/{order}/setujui', [OrderController::class, 'approve'])->name('order.approve');
    Route::post('/order/{order}/tolak', [OrderController::class, 'reject'])->name('order.reject');

    Route::get('/opname', [OpnameController::class, 'index'])->name('opname');
    Route::post('/opname', [OpnameController::class, 'store'])->name('opname.store');
    Route::get('/opname/{opname}', [OpnameController::class, 'show'])->name('opname.show');
    Route::post('/opname/{opname}/posting', [OpnameController::class, 'post'])->name('opname.post');
});
