<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Menu;
use App\Models\Category;
use App\Services\CommonService;

class CashierService
{
    protected $commonService;

    public function __construct(CommonService $commonService)
    {
        $this->commonService = $commonService;
    }
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
        return $this->commonService->getAll(Category::class);
    }

    /**
     * 全メニュー（カテゴリ情報付き）を取得
     */
    public function getAllMenus()
    {
        return $this->commonService->getAll(Menu::class, ['category']);
    }

    /**
     * 注文IDで注文情報を取得
     */
    public function findOrderWithItems($orderId)
    {
        return $this->commonService->transaction(function() use ($orderId) {
            return Order::where('id', $orderId)->lockForUpdate()->with('items.menu')->firstOrFail();
        });
    }

    /**
     * メニューIDでメニューを取得
     */
    public function findMenu($menuId)
    {
        return $this->commonService->transaction(function() use ($menuId) {
            return Menu::where('id', $menuId)->lockForUpdate()->firstOrFail();
        });
    }
}
