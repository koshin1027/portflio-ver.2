<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Category;
use App\Models\Menu;

class KitchenService
{
    public function getCategories()
    {
        return Category::all();
    }

    public function getMenus()
    {
        return Menu::with('category')->get();
    }

    /**
     * ステータスでフィルターした注文一覧を取得
     * 'all' なら全件取得
     */
    public function getOrders(string $status = 'all')
    {
        if ($status === 'all') {
            return Order::with('items.menu')->latest()->get();
        }
        return Order::with('items.menu')->where('status', $status)->latest()->get();
    }

    /**
     * 注文の件数カウント
     */
    public function countOrders()
    {
        return [
            'all' => Order::count(),
            'new' => Order::where('status', 'new')->count(),
            'preparing' => Order::where('status', 'preparing')->count(),
            'ready' => Order::where('status', 'ready')->count(),
            'delivered' => Order::where('status', 'delivered')->count(),
        ];
    }

    /**
     * 注文ステータスを更新
     */
    public function updateOrderStatus(int $orderId, string $status)
    {
        $order = Order::findOrFail($orderId);
        $order->status = $status;
        $order->save();
    }

    /**
     * 注文を配達済みにする
     */
    public function deliverOrder(int $orderId)
    {
        $order = Order::findOrFail($orderId);
        $order->status = 'delivered';
        $order->delivered_time = now()->format('H:i');
        $order->save();
    }
}
