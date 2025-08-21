<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

use App\Http\Controllers\StartUpController;
use App\Http\Controllers\ModeController;
use App\Http\Controllers\ManagementController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\KitchenController;
use App\Http\Controllers\CashierController;
use App\Http\Controllers\PageController;

Route::get('/', function() {
    if (auth()->check()) {
        return redirect()->route('dashboard'); // ログイン済みならmode画面へ
    }
    return redirect()->route('login'); // 未ログインなら登録画面
});

Route::get('/dashboard', function () {
            return view('dashboard');
        })->name('dashboard');

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/mode', [PageController::class, 'mode'])->name('mode');
    Route::get('/order', [PageController::class, 'order'])->name('order');
    Route::get('/kitchen', [PageController::class, 'kitchen'])->name('kitchen');
    Route::get('/cashier', [PageController::class, 'cashier'])->name('cashier');
    Route::get('/management', [PageController::class, 'management'])->name('management');
});