<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
use App\Http\Controllers\PageController;

// 未ログイン  ->  ログイン画面へ
// ログイン済　->  ダッシュボード
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

//ミドルウェアでログインチェック
//URLにアクセスする度に、アクセス制御をコントローラーで実行
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/dashboard', [PageController::class, 'dashboard'])->name('dashboard');
    Route::get('/mode', [PageController::class, 'mode'])->name('mode');
    Route::get('/order', [PageController::class, 'order'])->name('order');
    Route::get('/kitchen', [PageController::class, 'kitchen'])->name('kitchen');
    Route::get('/cashier', [PageController::class, 'cashier'])->name('cashier');
    Route::get('/management', [PageController::class, 'management'])->name('management');
});