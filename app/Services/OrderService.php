<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use App\Models\Menu;
use App\Services\CommonService;

class OrderService
{
    protected $commonService;

    public function __construct(CommonService $commonService)
    {
        $this->commonService = $commonService;
    }
    public function getFilteredMenus(?int $categoryId, ?string $search)
    {
        //トランザクション処理
        $this->commonService->transaction(function() use(&$query) {
            $query = Menu::with('category');
        });

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        // 全件取得部分を共通サービス経由に
        if (!$categoryId && empty($search)) {
            return $this->commonService->getAll(Menu::class, ['category']);
        }
        return $query->get();
    }

    // 注文履歴を取得（最新n件）
    public function getRecentOrders()
    {
        return Order::with('items.menu')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * 注文番号から注文情報を取得し、カート形式の配列で返す
     * 見つからなければ null を返す
     */
    public function getCartByOrderNumber(string $orderNumber): ?array
    {
        $order = Order::where('number', $orderNumber)->first();
        if (!$order) {
            return null;
        }

        $items = OrderItem::where('order_id', $order->id)->get();
        $cart = [];

        foreach ($items as $item) {
            $menu = $item->menu; // 関連モデルmenuを利用
            if ($menu) {
                $cart[] = [
                    'id' => $menu->id,
                    'name' => $menu->name,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                ];
            }
        }

        return $cart;
    }

    public function addToCart(array $cart, int $menuId): array
    {
        $menu = $this->commonService->getAll(Menu::class)->find($menuId);

        // メニューが存在しない場合はカートを変更しないで返す
        if (!$menu) {
            return $cart;
        }

        //カート内で同じ商品があれば個数を増やす
        foreach ($cart as &$item) {
            if ($item['id'] === $menu->id) {
                $item['quantity']++;
                return $cart;
            }
        }

        //同じ商品がなければ新規作成
        $cart[] = [
            'id' => $menu->id,
            'name' => $menu->name,
            'price' => $menu->price,
            'quantity' => 1,
        ];

        return $cart;
    }

    //注文作成処理
    public function createOrder(array $cart): array
    {
        $orderNumber = date('Ymd') . '-' . mt_rand(1000, 9999);

        DB::beginTransaction();

        try {
            $order = new Order();
            $order->number = $orderNumber;
            $order->total = collect($cart)->reduce(fn($total, $item) => $total + ($item['price'] * $item['quantity']), 0);
            $order->status = '注文中';
            $order->save();

            foreach ($cart as $item) {
                $orderItem = new OrderItem();
                $orderItem->order_id = $order->id;
                $orderItem->menu_id = $item['id'];
                $orderItem->quantity = $item['quantity'];
                $orderItem->price = $item['price'];
                $orderItem->save();
            }

            DB::commit();
            return ['success' => true, 'order_number' => $orderNumber];
        } catch (\Exception $e) {
            DB::rollBack();
            return ['success' => false];
        }
    }
}
