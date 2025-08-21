<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

use App\Http\Controllers\StartUpController;
use App\Http\Controllers\ModeController;
use App\Http\Controllers\ManagementController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\KitchenController;
use App\Http\Controllers\CashierController;

//トップページは新規登録画面へリダイレクト
Route::get('/', function() {
    return redirect()->route('register');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    //ログイン後のスタートアップ画面
    Route::get('/startup', function() {
        return view('livewire.start-up');
    })->name('startup');
    // 管理者(admin)
    Route::middleware('role:admin')->group(function () {
        Route::get('/mode', [ModeController::class, 'index'])->name('mode');
        Route::get('/management', [ManagementController::class, 'index'])->name('management');
        Route::get('/kitchen', [KitchenController::class, 'index'])->name('kitchen');
        Route::get('/cashier', [CashierController::class, 'index'])->name('cashier');
        Route::get('/order', [OrderController::class, 'index'])->name('order');
        Route::get('/dashboard', function () {
            return view('dashboard');
        })->name('dashboard');
    });

    // スタッフ(staff)
    Route::middleware('role:staff')->group(function () {
        Route::get('/kitchen', [KitchenController::class, 'index'])->name('kitchen');
        Route::get('/cashier', [CashierController::class, 'index'])->name('cashier');
    });

    // お客様(customer)
    Route::middleware('role:customer')->group(function () {
        Route::get('/order', [OrderController::class, 'index'])->name('order');
    });

});

//テスト用
Route::get('/test-age', function () {
    return "アクセスOK!";
})->middleware('check.age:18');