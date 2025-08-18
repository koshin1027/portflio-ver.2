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

    public function __construct(CommonService $commonService)
    {
        $this->commonService = $commonService;
    }

    public function validateMenu(array $data)
    {
        $validator = Validator::make($data, $this->menuRules);
        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }
    }

    public function searchMenus($activeCategoryId, $search, $filterStatus, $sortPrice)
    {
        //トランザクション処理
        $this->commonService->transaction(function() use(&$query) {
            $query = Menu::with('category');
        });

        if (!empty($activeCategoryId)) {
             $query->where('category_id', $activeCategoryId);
        }

        if (!empty($search)) {
             $query->where('name', 'like', '%' . $search . '%');
        }

        if (!empty($filterStatus) && $filterStatus !== 'すべての状態') {
             $query->where('status', $filterStatus);
        }

        if ($sortPrice === '低い順') {
            $query->orderBy('price', 'asc');
        } elseif ($sortPrice === '高い順') {
             $query->orderBy('price', 'desc');
        }

        return  $query->paginate(5);
    }

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

    public function updateMenu(Menu $menu, array $data) : void
    {
        // バリデーション
        $this->validateMenu($data);
        
        // トランザクション処理
        $this->commonService->transaction(function() use($data, $menu) {
            // ロック処理
            $menu = Menu::where('id', $menu->id)->lockForUpdate()->first();

            if (!$menu) {
                throw new \Exception("Menu with ID {$menu->id} not found.");
            }

            // 更新処理
            $menu->update($data);
        });
    }

    public function deleteMenu($deleteMenuId)
    {
        // トランザクション処理
        $this->commonService->transaction(function() use($deleteMenuId) {

            //ロック処理
            $menu = Menu::where('id', $deleteMenuId)->lockForUpdate()->first();

            if (!$menu) {
                throw new \Exception("Menu with ID {$deleteMenuId} not found.");
            }

            // 削除処理
            $menu->delete();
        });
    }
}
