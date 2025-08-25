<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Category;
use App\Models\Menu;
use App\Services\CashierService;

class CashierSystem extends Component
{
    // サービスクラス（CashierService）を保持
    protected $cashierService = null;

    // サービス取得用ヘルパー（DI不整合回避用）
    protected function getCashierService()
    {
        if ($this->cashierService) {
            return $this->cashierService;
        }
        return app(CashierService::class);
    }
    
    // データバインディング用プロパティ
    public $categories;
    public $menus;
    public $allMenus;
    public $cart = [];
    public $subtotal = 0;
    public $tax = 0;
    public $total = 0;
    public $selectedTable = null;
    public $orderNumberCandidates = [];
    public $currentOrderNumber = null;
    public $clock;
    public $showTableModal = false;
    public $showCheckoutModal = false;
    public $showPaymentCompleteModal = false;
    public $selectedCategory = null;
    public $orderInfo = null; // 検索結果の注文情報保持用
    public $receivedAmount = 0; // 預かり金額
    public $selectedPaymentMethod = null; // 支払い方法

    // 会計完了モーダル用
    public $completeOrderNumber = '';
    public $completeTotalAmount = '';
    public $completeChangeAmount = '';
    public $completeOrderItems = [];

    // 初期化
    public function mount(CashierService $cashierService)
    {
        $this->cashierService = $cashierService;

        $service = $this->getCashierService();
        $this->orderNumberCandidates = $service->getOrderNumberCandidates();
        $this->categories = $service->getCategories();
        $this->allMenus = $service->getAllMenus();
        $this->selectedCategory = $this->categories->first() ? $this->categories->first()->name : null;
        $this->menus = $this->allMenus->where('category.name', $this->selectedCategory)->values();
        $this->updateClock();
    }

    // 描画
    public function render()
    {
        return view('livewire.cashier-system');
    }

    // 前画面(mode)に戻る
    public function backToStart()
    {
        return redirect()->route('mode');
    }

    // 注文番号検索
    public function searchOrder()
    {
        $this->updatedCurrentOrderNumber($this->currentOrderNumber);
    }

    // 注文番号変更時に注文情報を取得
    public function updatedCurrentOrderNumber($value)
    {
        $order = $this->getCashierService()->findOrderWithItems($value);

        if ($order) {
            $this->orderInfo = [
                'id' => $order->id,
                'table' => $order->table_number ?? '-',
                'status' => $order->status ?? '注文中',
                'items' => $order->items ?? [],
            ];

            $this->cart = $order->items ? $order->items->map(function($item) {
                return [
                    'id' => $item->menu_id,
                    'name' => $item->menu->name ?? '',
                    'price' => $item->menu->price ?? 0,
                    'quantity' => $item->quantity,
                ];
            })->toArray() : [];

            $this->updateCart();
            $this->selectedTable = $order->table_number ?? null;

        } else {
            $this->orderInfo = null;
            $this->cart = [];
            $this->updateCart();
            $this->selectedTable = null;
        }
    }

    // 現在時刻更新
    public function updateClock()
    {
        $this->clock = now()->format('H:i:s');
    }

    // カートにメニューを追加
    public function addToCart($menuId)
    {
        $menu = $this->getCashierService()->findMenu($menuId);

        foreach ($this->cart as &$item) {
            if ($item['id'] === $menu->id) {
                $item['quantity']++;
                $this->updateCart();
                return;
            }
        }

        $this->cart[] = [
            'id' => $menu->id,
            'name' => $menu->name,
            'price' => $menu->price,
            'quantity' => 1
        ];

        $this->updateCart();
    }

    // カート内の数量を増やす
    public function increaseQuantity($index)
    {
        $this->cart[$index]['quantity']++;
        $this->updateCart();
    }

    // カート内の数量を減らす
    public function decreaseQuantity($index)
    {
        $this->cart[$index]['quantity']--;

        if ($this->cart[$index]['quantity'] <= 0) {
            array_splice($this->cart, $index, 1);
        }

        $this->updateCart();
    }

    // カートをクリア
    public function clearCart()
    {
        $this->cart = [];
        $this->updateCart();
    }

    // 小計・消費税・合計の計算
    public function updateCart()
    {
        $this->subtotal = collect($this->cart)->sum(fn($item) => $item['price'] * $item['quantity']);
        $this->tax = floor($this->subtotal * 0.1);
        $this->total = $this->subtotal + $this->tax;

        $this->updateChangeAmount();
    }

    // テンキーで受取金額を入力
    public function inputReceivedAmount($value)
    {
        $this->receivedAmount = (int)($this->receivedAmount . $value);
        $this->updateChangeAmount();
    }

    // 受取金額をクリア
    public function clearReceivedAmount()
    {
        $this->receivedAmount = 0;
        $this->updateChangeAmount();
    }

    // 受取金額を合計金額に揃える
    public function exactReceivedAmount()
    {
        $this->receivedAmount = $this->total;
        $this->updateChangeAmount();
    }

    // お釣り計算
    public function updateChangeAmount()
    {
        $this->changeAmount = max(0, $this->receivedAmount - $this->total);
    }

    // テーブル選択モーダル
    public function openTableModal()
    {
        $this->showTableModal = true;
    }
    public function closeTableModal()
    {
        $this->showTableModal = false;
    }
    public function selectTable($table)
    {
        $this->selectedTable = $table;
        $this->closeTableModal();
    }

    // チェックアウトモーダル
    public function openCheckoutModal()
    {
        $this->showCheckoutModal = true;
    }
    public function closeCheckoutModal()
    {
        $this->showCheckoutModal = false;
    }

    // 会計完了モーダル
    public function openPaymentCompleteModal()
    {
        $this->showPaymentCompleteModal = true;
    }
    public function closePaymentCompleteModal()
    {
        $this->showPaymentCompleteModal = false;
    }

    // 支払い方法選択
    public function selectPaymentMethod($method)
    {
        $this->selectedPaymentMethod = $method;
    }

    // 注文保存・会計完了処理
    public function saveOrder()
    {
        $this->completeOrderNumber = $this->currentOrderNumber ?? '';
        $this->completeTotalAmount = $this->total ?? '';
        $this->completeChangeAmount = $this->changeAmount ?? '';
        $this->completeOrderItems = $this->cart;

        $this->cart = [];
        $this->updateCart();
        $this->receivedAmount = 0;
        $this->changeAmount = 0;

        $this->closeCheckoutModal();
        $this->openPaymentCompleteModal();
    }

    // カテゴリーでメニューを絞り込む
    public function filterCategory($category)
    {
        $this->selectedCategory = $category;
        $this->menus = $this->allMenus->where('category.name', $category)->values();
    }
}
