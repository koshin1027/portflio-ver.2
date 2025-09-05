<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Category;
use App\Models\Menu;
use App\Services\OrderService;

class OrderMenu extends Component
{

    //注文履歴モーダルの制御プロパティ
    public $showOrderHistory = false;
    public $orderHistory;

    //
    public $searchOrderNumber = '';

    // カテゴリー制御プロパティ
    public $activeCategoryId = null;
    public $categories;
    public $menus;
    public $cart = [];

    //注文確認モーダル制御プロパティ
    public $showConfirmModal = false;

    //注文完了モーダル制御プロパティ
    public $showCompleteModal = false;

    //
    public $orderNumber = null;

    //
    public $clock;

    //検索制御プロパティ
    public $search = '';

    protected OrderService $orderService;

    //依存性注入
    public function boot(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    //初期化
    public function mount()
    {
        $this->categories = Category::all();
        $this->activeCategoryId = $this->categories->first()->id;
        $this->filterMenus();
    }

    //再描画時の処理
    public function render()
    {
        return view('livewire.order-menu');
    }

    //フィルタリングされたメニューを取得
    public function filterMenus()
    {
        //orderServiceから「メニューのフィルタリング機能」を移譲
        $this->menus = $this->orderService->getFilteredMenus($this->activeCategoryId, $this->search);
    }

    // 注文履歴モーダルの制御処理

    //オープン
    public function openOrderHistory()
    {
        //orderServiceから「??機能」を移譲
        $this->orderHistory = $this->orderService->getRecentOrders(10);
        $this->showOrderHistory = true;
    }

    //クローズ
    public function closeOrderHistory()
    {
        $this->showOrderHistory = false;
    }

    //選択されたカテゴリーをアクティブカテゴリーにセット
    public function setActiveCategory($id)
    {
        $this->activeCategoryId = $id;
        $this->filterMenus();
    }

    //
    public function searchMenus()
    {
        $this->filterMenus();
    }

    //
    public function searchOrder()
    {
        if (empty($this->searchOrderNumber)) return;

        //orderServiceから「??機能」を移譲
        $cart = $this->orderService->getCartByOrderNumber($this->searchOrderNumber);

        if ($cart === null) {
            session()->flash('error', '該当する注文が見つかりません');
            return;
        }

        $this->cart = $cart;
    }

    //カートに追加する
    public function addToCart($menuId)
    {
        //orderServiceから「??機能」を移譲
        $this->cart = $this->orderService->addToCart($this->cart, $menuId);
    }

    //カートの個数を増やす
    public function increaseQuantity($index)
    {
        $this->cart[$index]['quantity']++;
    }

    //カートの個数を減らす制御
    public function decreaseQuantity($index)
    {
        $this->cart[$index]['quantity']--;

        //個数が0未満なら配列から削除
        if ($this->cart[$index]['quantity'] <= 0) {
            array_splice($this->cart, $index, 1);
        }
    }

    //カートをクリア
    public function clearCart()
    {
        $this->cart = [];
    }

    // 注文確定モーダルの制御処理

    //オープン
    public function showConfirm()
    {
        $this->showConfirmModal = true;
    }

    //クローズ
    public function hideConfirm()
    {
        $this->showConfirmModal = false;
    }

    //注文の確定処理
    public function confirmOrder()
    {
        //orderServiceから「注文を確定させる機能」を移譲
        //結果を$resultに格納する
        $result = $this->orderService->createOrder($this->cart);

        //成功
        //開いているモーダルを閉じ、完了モーダルを表示
        //カートをクリア
        if ($result['success']) {
            $this->orderNumber = $result['order_number'];
            $this->showConfirmModal = false;
            $this->showCompleteModal = true;
            $this->clearCart();
        } 
        //失敗
        //フラッシュメッセージを表示
        else {
            session()->flash('error', '注文の保存に失敗しました');
        }
    }

    //注文確定モーダル

    //オープン
    //注文確定時に自動で開くため未実装

    //クローズ
    public function hideComplete()
    {
        $this->showCompleteModal = false;

        //カートをクリア
        $this->clearCart();
    }
}
