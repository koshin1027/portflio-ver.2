<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

//ユーザ情報(role)を参照してアクセス制御
class PageController extends Controller
{
    //全ユーザー許可
    public function dashboard()
    {
        return view('dashboard');
    }

    //全ユーザー許可
    public function mode()
    {
        $user = Auth::user();
        if ($user->role) {
            return view('mode'); // resources/views/mode.blade.php
        }

        abort(403, 'アクセス権限がありません');
    }

    // 注文画面(adminとcustomer)
    public function order()
    {
        $user = Auth::user();

        if ($user->role === 'admin' || $user->role === 'customer') {
            return view('order'); // resources/views/order.blade.php
        }

        abort(403, 'アクセス権限がありません');
    }

    // キッチン画面(adminとstaff)
    public function kitchen()
    {
        $user = Auth::user();

        if ($user->role === 'admin' || $user->role === 'staff') {
            return view('kitchen'); // resources/views/kitchen.blade.php
        }

        abort(403, 'アクセス権限がありません');
    }

    // レジ画面(adminとstaff)
    public function cashier()
    {
        $user = Auth::user();

        if ($user->role === 'admin' || $user->role === 'staff') {
            return view('cashier'); // resources/views/cashier.blade.php
        }

        abort(403, 'アクセス権限がありません');
    }

    // 管理画面(admin)
    public function management()
    {
        $user = Auth::user();

        if ($user->role === 'admin') {
            return view('management'); // resources/views/management.blade.php
        }

        abort(403, 'アクセス権限がありません');
    }
}