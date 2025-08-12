<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\KitchenService;

class KitchenOrders extends Component
{
    protected KitchenService $kitchenService;

    public $categories;
    public $menus;
    public $clock;
    public $status = 'all';
    public $orders = [];
    public $countAll, $countNew, $countPreparing, $countReady, $countDelivered;

    public function boot(KitchenService $kitchenService)
    {
        $this->kitchenService = $kitchenService;
    }

    public function mount()
    {
        $this->categories = $this->kitchenService->getCategories();
        $this->menus = $this->kitchenService->getMenus();
        $this->fetchOrders();
    }

    public function render()
    {
        return view('livewire.kitchen-orders');
    }

    public function backToStart()
    {
        return redirect()->route('mode');
    }

    public function setStatus($status)
    {
        $this->status = $status;
        $this->fetchOrders();
    }

    public function fetchOrders()
    {
        $this->orders = $this->kitchenService->getOrders($this->status);

        $counts = $this->kitchenService->countOrders();
        $this->countAll = $counts['all'];
        $this->countNew = $counts['new'];
        $this->countPreparing = $counts['preparing'];
        $this->countReady = $counts['ready'];
        $this->countDelivered = $counts['delivered'];

        $this->clock = now()->format('H:i:s');
    }

    public function changeOrderStatus($orderId, $status)
    {
        $this->kitchenService->updateOrderStatus($orderId, $status);
        $this->fetchOrders();
    }

    public function deliverOrder($orderId)
    {
        $this->kitchenService->deliverOrder($orderId);
        $this->fetchOrders();
    }
}
