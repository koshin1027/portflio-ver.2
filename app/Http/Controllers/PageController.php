<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PageController extends Controller
{
    public function mode()
    {
        $user = Auth::user();
        if ($user->role) {
            return view('mode'); // admin はすべてOK
        }
        abort(403);
    }

        // 注文画面
    public function order()
    {
        $user = Auth::user();

        if ($user->role === 'admin' || $user->role === 'customer') {
            return view('order'); // resources/views/order.blade.php
        }

        abort(403, 'アクセス権限がありません');
    }

    // キッチン画面
    public function kitchen()
    {
        $user = Auth::user();

        if ($user->role === 'admin' || $user->role === 'staff') {
            return view('kitchen'); // resources/views/kitchen.blade.php
        }

        abort(403, 'アクセス権限がありません');
    }

    // レジ画面
    public function cashier()
    {
        $user = Auth::user();

        if ($user->role === 'admin' || $user->role === 'staff') {
            return view('cashier'); // resources/views/cashier.blade.php
        }

        abort(403, 'アクセス権限がありません');
    }

    // 管理画面
    public function management()
    {
        $user = Auth::user();

        if ($user->role === 'admin') {
            return view('management'); // resources/views/management.blade.php
        }

        abort(403, 'アクセス権限がありません');
    }
}