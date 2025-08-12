<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Menu;
use App\Models\Category;

class CashierService
{
    /**
     * 未完了の注文ID一覧を取得
     */
    public function getOrderNumberCandidates()
    {
        return Order::whereIn('status', ['new', 'preparing', 'ready'])
            ->orderBy('created_at', 'desc')
            ->pluck('id')
            ->toArray();
    }

    /**
     * カテゴリ一覧を取得
     */
    public function getCategories()
    {
        return Category::all();
    }

    /**
     * 全メニュー（カテゴリ情報付き）を取得
     */
    public function getAllMenus()
    {
        return Menu::with('category')->get();
    }

    /**
     * 注文IDで注文情報を取得
     */
    public function findOrderWithItems($orderId)
    {
        return Order::with('items.menu')->find($orderId);
    }

    /**
     * メニューIDでメニューを取得
     */
    public function findMenu($menuId)
    {
        return Menu::findOrFail($menuId);
    }
}
