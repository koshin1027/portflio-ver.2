<?php

namespace App\Services;
use App\Models\Menu;
use App\Services\CommonService;
use Illuminate\Support\Facades\Validator;

class ManagementService
{
    protected $commonService;

    private array $menuRules = [
        'name' => 'required|string|max:255',
        'price' => 'required|numeric|min:0',
        'category_id' => 'required|exists:categories,id',
        'status' => 'nullable|string',
        'amount' => 'nullable|integer|min:0',
        'explanation' => 'nullable|string',
        'images' => 'nullable|string',
    ];

    //初期化
    public function __construct(CommonService $commonService)
    {
        //依存性注入
        $this->commonService = $commonService;
    }

    //バリデーション
    public function validateMenu(array $data)
    {
        $validator = Validator::make($data, $this->menuRules);
        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }
    }

    //メニュー並び替え
    public function searchMenus($activeCategoryId, $search, $filterStatus, $sortPrice)
    {
        // トランザクション処理
        $this->commonService->transaction(function() use(&$query) {
            $query = $this->commonService->getAllWithRelations(Menu::class, ['category']);
        });

        //選択したカテゴリーのみを表示
        if (!empty($activeCategoryId)) {
            $query->where('category_id', $activeCategoryId);
        }

        //検索欄でヒットした商品のみを表示
        if (!empty($search)) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        //選択されたステータスの商品のみを表示
        if (!empty($filterStatus) && $filterStatus !== 'すべての状態') {
            $query->where('status', $filterStatus);
        }

        //昇順または降順で表示
        if ($sortPrice === '低い順') {
            $query->orderBy('price', 'asc');
        } elseif ($sortPrice === '高い順') {
            $query->orderBy('price', 'desc');
        }

        //ページネーション
        return $query->paginate(5);
    }

    //メニュー作成
    public function createMenu($name, $price, $category_id, $status, $amount, $explanation) : void
    {
        // メニュー新規作成
        $data = [
            'name' => $name,
            'price' => $price,
            'category_id' => $category_id,
            'status' => $status,
            'amount' => $amount,
            'explanation' => $explanation,
        ];

        // バリデーション
        $this->validateMenu($data);

        //トランザクション処理
        $this->commonService->transaction(function() use($data) {
            Menu::create($this->data);
        });
    }

    //メニュー更新
    public function updateMenu(Menu $menu, array $data) : void
    {
        // バリデーション
        $this->validateMenu($data);

        // トランザクション処理
        $this->commonService->transaction(function() use($data, $menu) {

            // ロック付きでメニューを取得
            $menu = $this->commonService->findWithLock(Menu::class, $menu->id);

            // 更新処理
            $menu->update($data);
        });
    }

    //メニュー削除
    public function deleteMenu($deleteMenuId)
    {
        // トランザクション処理
        $this->commonService->transaction(function() use($deleteMenuId) {
            
            // ロック付きでメニューを取得
            $menu = $this->commonService->findWithLock(Menu::class, $deleteMenuId);

            // 削除処理
            $menu->delete();
        });
    }
}
