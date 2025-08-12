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
