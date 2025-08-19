<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\KitchenService;

class KitchenOrders extends Component
{
    protected KitchenService $kitchenService;

    //
    public $categories;
    public $menus;
    public $clock;
    public $status = 'all';
    public $orders = [];
    public $countAll, $countNew, $countPreparing, $countReady, $countDelivered;


    //依存性注入
    public function boot(KitchenService $kitchenService)
    {
        $this->kitchenService = $kitchenService;
    }

    //初期化
    public function mount()
    {
        $this->categories = $this->kitchenService->getCategories();
        $this->menus = $this->kitchenService->getMenus();
        $this->fetchOrders();
    }

    //描画時の処理
    public function render()
    {
        return view('livewire.kitchen-orders');
    }


    //前画面(mode)に戻る
    public function backToStart()
    {
        return redirect()->route('mode');
    }


    //現在のステータスをstatusプロパティにセット
    public function setStatus($status)
    {
        $this->status = $status;
        $this->fetchOrders();
    }

    //??
    public function fetchOrders()
    {
        //kitchenServiceから「ステータスでフィルターした注文一覧を取得する機能」を移譲
        $this->orders = $this->kitchenService->getOrders($this->status);

        //kitchenServiceから「ステータスごとの件数を取得する機能」を移譲
        $counts = $this->kitchenService->countOrders();

        //ステータスごとの件数を対応するプロパティに代入
        $this->countAll = $counts['all'];
        $this->countNew = $counts['new'];
        $this->countPreparing = $counts['preparing'];
        $this->countReady = $counts['ready'];
        $this->countDelivered = $counts['delivered'];
        $this->clock = now()->format('H:i:s');
    }

    //オーダーのステータスを変更する処理
    public function changeOrderStatus($orderId, $status)
    {
        //kitchenServiceから「ステータスをアップデートする機能」を移譲
        $this->kitchenService->updateOrderStatus($orderId, $status);
        $this->fetchOrders();
    }

    //ステータスを配達済みに変更する処理
    public function deliverOrder($orderId)
    {
         //kitchenServiceから「ステータスを配達済みに変更する機能」を移譲
        $this->kitchenService->deliverOrder($orderId);
        $this->fetchOrders();
    }
}
