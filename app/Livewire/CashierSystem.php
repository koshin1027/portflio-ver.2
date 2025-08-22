<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Redirector;
use App\Models\Category;
use App\Models\Menu;
use App\Services\CashierService;

class CashierSystem extends Component
{
    // LivewireのDIの都合で型宣言を外し、初期値nullで定義
    // DIの不整合を避けるため、サービス取得用ヘルパーを用意
    protected $cashierService = null;

    protected function getCashierService()
    {
        if ($this->cashierService) {
            return $this->cashierService;
        }
        return app(CashierService::class);
    }
    
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

    // public function boot(CashierService $cashierService)
    // {
    //     $this->cashierService = $cashierService;
    // }

    //初期化
    public function mount(CashierService $cashierService)
    {
        $this->cashierService = $cashierService;
        // サービス経由でDB取得
        $service = $this->getCashierService();
        $this->orderNumberCandidates = $service->getOrderNumberCandidates();
        $this->categories = $service->getCategories();
        $this->allMenus = $service->getAllMenus();
        $this->selectedCategory = $this->categories->first() ? $this->categories->first()->name : null;
        $this->menus = $this->allMenus->where('category.name', $this->selectedCategory)->values();
        $this->updateClock();
    }


    //描画
    public function render()
    {
        return view('livewire.cashier-system');
    }

    //前画面(mode)に戻る
    public function backToStart()
    {
        return redirect()->route('mode');
    }

    //
    public function searchOrder()
    {
        $this->updatedCurrentOrderNumber($this->currentOrderNumber);
    }

    // 注文番号を検索
    public function updatedCurrentOrderNumber($value)
    {
        //getCashierServiceから「??機能」を移譲
        $order = $this->getCashierService()->findOrderWithItems($value);

        //$orderが存在するなら、$orderInfoに検索した注文情報を定義
        if ($order) {
            $this->orderInfo = [
                'id' => $order->id,
                'table' => $order->table_number ?? '-',
                'status' => $order->status ?? '注文中',
                'items' => $order->items ?? [],
            ];

            // カート内容も反映
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

    //時計処理
    public function updateClock()
    {
        $this->clock = now()->format('H:i:s');
    }

    //カート追加処理
    public function addToCart($menuId)
    {
        //getCashierServiceから「??機能」を移譲
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

    //個数を増やす機能
    public function increaseQuantity($index)
    {
        $this->cart[$index]['quantity']++;
        $this->updateCart();
    }

    //個数を減らす処理
    public function decreaseQuantity($index)
    {
        $this->cart[$index]['quantity']--;

        //もし0以下なら削除
        if ($this->cart[$index]['quantity'] <= 0) {
            array_splice($this->cart, $index, 1);
        }
        $this->updateCart();
    }

    //カートをクリアする機能
    public function clearCart()
    {
        $this->cart = [];
        $this->updateCart();
    }

    //カート反映処理
    public function updateCart()
    {
        $this->subtotal = collect($this->cart)->sum(fn($item) => $item['price'] * $item['quantity']);
        $this->tax = floor($this->subtotal * 0.1);
        $this->total = $this->subtotal + $this->tax;
        // お釣り再計算
        $this->updateChangeAmount();

    }

    //??機能
    public function inputReceivedAmount($value)
    {
        // テンキー入力値を加算
        $this->receivedAmount = (int)($this->receivedAmount . $value);
        $this->updateChangeAmount();
    }

    //??機能
    public function clearReceivedAmount()
    {
        $this->receivedAmount = 0;
        $this->updateChangeAmount();
    }

    //??機能
    public function exactReceivedAmount()
    {
        $this->receivedAmount = $this->total;
        $this->updateChangeAmount();
    }

    //??機能
    public function updateChangeAmount()
    {
        $this->changeAmount = max(0, $this->receivedAmount - $this->total);
    }

    //??機能
    public function openTableModal()
    {
        $this->showTableModal = true;
    }

    //??機能
    public function closeTableModal()
    {
        $this->showTableModal = false;
    }

    //??機能
    public function selectTable($table)
    {
        $this->selectedTable = $table;
        $this->closeTableModal();
    }

    //??機能
    public function openCheckoutModal()
    {
        $this->showCheckoutModal = true;
    }

    //??機能
    public function closeCheckoutModal()
    {
        $this->showCheckoutModal = false;
    }

    //??機能
    public function openPaymentCompleteModal()
    {
        $this->showPaymentCompleteModal = true;
    }

    //??機能
    public function closePaymentCompleteModal()
    {
        $this->showPaymentCompleteModal = false;
    }

    //??機能
    public function selectPaymentMethod($method)
    {
        $this->selectedPaymentMethod = $method;
    }

    //??機能
    public function saveOrder()
    {
        // 会計完了モーダル用の値をセット
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

    //カテゴリー選択
    public function filterCategory($category)
    {
        $this->selectedCategory = $category;
        $this->menus = $this->allMenus->where('category.name', $category)->values();
    }

}


