<?php

namespace App\Services;
use App\Models\Menu;
use Illuminate\Support\Facades\Validator;

class ManagementService
{
    public function __construct()
    {
        //
    }

    private array $menuRules = [
        'name' => 'required|string|max:255',
        'price' => 'required|numeric|min:0',
        'category_id' => 'required|exists:categories,id',
        'status' => 'nullable|string',
        'amount' => 'nullable|integer|min:0',
        'explanation' => 'nullable|string',
        'images' => 'nullable|string',
    ];

    public function validateMenu(array $data)
    {
        $validator = Validator::make($data, $this->menuRules);
        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }
    }

    public function searchMenus($activeCategoryId, $search, $filterStatus,$sortPrice)
    {
        //プロパティを参照して表示するメニューを表示

        $query = Menu::with('category');

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

        return $query->paginate(5);
    }

    public function createMenu($name, $price, $category_id, $status, $amount, $explanation)
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

        return Menu::create($data);
    }

    public function updateMenu(Menu $menu, array $data)
    {
        // バリデーション
        $this->validateMenu($data);

        // 更新処理
        return $menu->update($data);
    }

    public function deleteMenu($deleteMenuId)
    {
        $menu = Menu::find($deleteMenuId);
        $menu->delete();
    }
}
