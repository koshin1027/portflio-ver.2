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

    //依存性注入
    public function __construct(CommonService $commonService)
    {
        $this->commonService = $commonService;
    }

    //特定条件でフィルタリングされたメニューを表示する
    public function getFilteredMenus(?int $categoryId, ?string $search)
    {
        //トランザクション処理
        $this->commonService->transaction(function() use(&$query) {
            $query = Menu::with('category');
        });

        //カテゴリーで検索して表示
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        //検索して表示
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        // 全件取得
        if (!$categoryId && empty($search)) {
            return $this->commonService->getAll(Menu::class, ['category']);
        }
        return $query->get();
    }

    // 注文履歴を取得
    public function getRecentOrders()
    {
        return Order::with('items.menu')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    //注文番号から注文情報を取得し,カートに表示する
    public function getCartByOrderNumber(string $orderNumber): ?array
    {
        return $this->commonService->transaction(function () use ($orderNumber) {
            //ロック付き注文番号を取得
            $order = $this->commonService->findWithLock(Order::class, $orderNumber);

            //注文番号が存在しないならnullを返す
            if (!$order) {
                return null;
            }

            //OrderItemモデルからidが一致する情報を取得
            $items = OrderItem::where('order_id', $order->id)->get();

            //カートをリセット
            $cart = [];

            //取得した情報をカート配列に格納する
            foreach ($items as $item) {
                $cart[] = [
                    'id' => $item->menu_id,
                    'name' => $item->menu->name,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                ];
            }

            return $cart;
        });
    }

    // カート追加処理
    public function addToCart(array $cart, int $menuId): array
    {
        return $this->commonService->transaction(function () use ($cart, $menuId) {
            //ロック付き注文番号を取得
            $menu = $this->commonService->findWithLock(Menu::class, $menuId);

            foreach ($cart as &$item) {
                //カートに同じ商品があれば、個数を＋1
                if ($item['id'] === $menu->id) {
                    $item['quantity']++;
                    return $cart;
                }
            }

            //カートに商品がなければ新規登録
            $cart[] = [
                'id' => $menu->id,
                'name' => $menu->name,
                'price' => $menu->price,
                'quantity' => 1,
            ];

            return $cart;
        });
    }

    //注文作成処理
    public function createOrder(array $cart): array
    {
        return $this->commonService->transaction(function () use ($cart) {
            //注文番号を生成 (作成日時＋ランダムな数字)
            $orderNumber = date('Ymd') . '-' . mt_rand(1000, 9999);

            //注文情報を$orderに格納(番号・金額・ステータス)
            $order = new Order();
            $order->number = $orderNumber;
            $order->total = collect($cart)->reduce(fn($total, $item) => $total + ($item['price'] * $item['quantity']), 0);
            $order->status = '注文中';
            $order->save();

            //orderItemにも注文情報を格納
            foreach ($cart as $item) {
                $orderItem = new OrderItem();
                $orderItem->order_id = $order->id;
                $orderItem->menu_id = $item['id'];
                $orderItem->quantity = $item['quantity'];
                $orderItem->price = $item['price'];
                $orderItem->save();
            }

            return ['success' => true, 'order_number' => $orderNumber];
        });
    }
}
