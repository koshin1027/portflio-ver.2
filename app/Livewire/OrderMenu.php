<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Category;
use App\Models\Menu;
use App\Services\OrderService;

class OrderMenu extends Component
{

    public $showOrderHistory = false;
    public $orderHistory;

    public $searchOrderNumber = '';
    public $activeCategoryId = null;
    public $categories;
    public $menus;
    public $cart = [];

    public $showConfirmModal = false;
    public $showCompleteModal = false;
    public $orderNumber = null;

    public $clock;
    public $search = '';

    protected OrderService $orderService;

    public function boot(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function mount()
    {
        $this->categories = Category::all();
        $this->activeCategoryId = $this->categories->first()->id;
        $this->filterMenus();
    }

    public function render()
    {
        return view('livewire.order-menu');
    }

    public function filterMenus()
    {
        $this->menus = $this->orderService->getFilteredMenus($this->activeCategoryId, $this->search);
    }

    //注文履歴の制御
    public function openOrderHistory()
    {
        $this->orderHistory = $this->orderService->getRecentOrders(10);
        $this->showOrderHistory = true;
    }

    public function closeOrderHistory()
    {
        $this->showOrderHistory = false;
    }

    //カテゴリー制御
    public function setActiveCategory($id)
    {
        $this->activeCategoryId = $id;
        $this->filterMenus();
    }

    public function searchMenus()
    {
        $this->filterMenus();
    }

    public function searchOrder()
    {
        if (empty($this->searchOrderNumber)) return;

        $cart = $this->orderService->getCartByOrderNumber($this->searchOrderNumber);

        if ($cart === null) {
            session()->flash('error', '該当する注文が見つかりません');
            return;
        }

        $this->cart = $cart;
    }

    //カートに追加する制御
    public function addToCart($menuId)
    {
        $this->cart = $this->orderService->addToCart($this->cart, $menuId);
    }

    //カートの個数を増やす制御
    public function increaseQuantity($index)
    {
        $this->cart[$index]['quantity']++;
    }

    //カートの個数を減らす制御
    public function decreaseQuantity($index)
    {
        $this->cart[$index]['quantity']--;
        if ($this->cart[$index]['quantity'] <= 0) {
            array_splice($this->cart, $index, 1);
        }
    }

    //カートをクリア
    public function clearCart()
    {
        $this->cart = [];
    }

    //注文確定モーダル制御
    public function showConfirm()
    {
        $this->showConfirmModal = true;
    }

    public function hideConfirm()
    {
        $this->showConfirmModal = false;
    }

    //注文確定処理
    public function confirmOrder()
    {
        $result = $this->orderService->createOrder($this->cart);

        if ($result['success']) {
            $this->orderNumber = $result['order_number'];
            $this->showConfirmModal = false;
            $this->showCompleteModal = true;
            $this->clearCart();
        } else {
            session()->flash('error', '注文の保存に失敗しました');
        }
    }

    //注文確定モーダル
    public function hideComplete()
    {
        $this->showCompleteModal = false;
        $this->clearCart();
    }
}
