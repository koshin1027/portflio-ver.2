<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Category;
use App\Models\Menu;
use App\Services\CommonService;

class KitchenService
{
    protected $commonService;

    public function __construct(CommonService $commonService)
    {
        //依存性注入
        $this->commonService = $commonService;
    }

    //カテゴリーを全取得
    public function getCategories()
    {
        return $this->commonService->getAll(Category::class);
    }

    //メニューを全取得
    public function getMenus()
    {
        return $this->commonService->getAll(Menu::class, ['category']);
    }

    //ステータスでフィルターした注文一覧を取得
    public function getOrders(string $status = 'all')
    {
        //'all' なら全件取得
        if ($status === 'all') {
            return Order::with('items.menu')->latest()->get();
        }

        //ステータスと一致する注文情報を取得
        return Order::with('items.menu')->where('status', $status)->latest()->get();
    }

    
    //注文の件数カウント
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

    //注文ステータスを更新
    public function updateOrderStatus(int $orderId, string $status)
    {
        $this->commonService->transaction(function() use ($orderId, $status) {
            $order = Order::where('id', $orderId)->lockForUpdate()->firstOrFail();
            $order->status = $status;
            $order->save();
        });
    }

    //注文を配達済みにする
    public function deliverOrder(int $orderId)
    {
        $this->commonService->transaction(function() use ($orderId) {
            $order = Order::where('id', $orderId)->lockForUpdate()->firstOrFail();
            $order->status = 'delivered';
            $order->delivered_time = now()->format('H:i');
            $order->save();
        });
    }
}
