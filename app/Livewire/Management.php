<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Category;
use App\Models\Menu;
use App\Services\ManagementService;

class Management extends Component

{
    use WithPagination;

    //サービス
    protected $menuService;

    //カテゴリータブ制御プロパティ
    public $categories;
    public $activeCategoryId;

    //検索制御プロパティ
    public $search = '';

    //メニュー制御プロパティ
    public $filterStatus = '';
    public $sortPrice = '';
    public $selectMenus;

    // モーダル制御プロパティ
    public $isAddModalOpen = false;
    public $isEditModalOpen = false;
    public $isDeleteModalOpen = false;

    // 編集対象ID
    public $editMenuId;
    public $deleteMenuId;

    // フォーム用プロパティ
    public $name = '';
    public $price = 0;
    public $category_id = '';
    public $status = '';
    public $amount = 0;
    public $explanation = '';
    public $images = '';

    public function boot(ManagementService $managementService)
    {
        $this->managementService = $managementService;
    }

    public function mount()
    {
        $this->categories = Category::all();
        $this->activeCategoryId = $this->categories->first()->id;
    }

    public function render()
    {
        // ManagementServiceサービスに委譲
        $menus = $this->managementService->searchMenus(
            $this->activeCategoryId,
            $this->search,
            $this->filterStatus,
            $this->sortPrice
        );

        return view('livewire.management',[
            'menus' => $menus,
        ]);
    }

    // フォームリセット
    public function resetForm()
    {
        $this->editMenuId = null;
        $this->selectMenus = null;
        $this->menus = null;
        $this->name = '';
        $this->price = 0;
        $this->category_id = '';
        $this->status = '';
        $this->amount = 0;
        $this->explanation = '';
        // $this->images = '';
    }

    //カテゴリータブで使用
    public function setActiveCategory($categoryId)
    {
        $this->activeCategoryId = $categoryId;
        $this->search = '';
    }

    // モーダル制御(注文追加)
    public function openAddModal()
    {
        $this->resetForm();
        $this->isAddModalOpen = true;
    }

    public function closeAddModal()
    {
        $this->isAddModalOpen = false;
    }

    // メニュー追加処理
    public function submit()
    {

        // ManagementServiceサービスに委譲
            $menus = $this->managementService->createMenu(
                $this->name,
                $this->price,
                $this->category_id,
                $this->status,
                $this->amount,
                $this->explanation,
                // $images,
            );

        //フォームリセット
        $this->resetForm();

        //モーダル閉じる
        $this->isAddModalOpen = false;
    }

    //モーダル制御(編集)
    public function openEditModal($id)
    {
        $this->selectMenus = Menu::find($id);

        $fields = ['name', 'price', 'category_id', 'status', 'amount', 'explanation'];

        foreach ($fields as $field) {
            $this->$field = $this->selectMenus->$field;
        }

        $this->editMenuId = $id;
        $this->isEditModalOpen = true;
    }

    // アップデート処理
    public function update()
    {

    $data = [
        'name' => $this->name,
        'price' => $this->price,
        'category_id' => $this->category_id,
        'status' => $this->status,
        'amount' => $this->amount,
        'explanation' => $this->explanation,
        // 'images' => $this->images,
    ];

    // ManagementServiceサービスに委譲（$this->selectMenusは更新対象モデル）
    $menus = $this->managementService->updateMenu($this->selectMenus, $data);

        //フォームリセット
        $this->resetForm();

        //モーダルを閉じる
        $this->isEditModalOpen = false;
    }

    public function closeEditModal()
    {
        $this->isEditModalOpen = false;
    }

    //モーダル制御(削除)
    public function openDeleteModal($id)
    {
        $this->deleteMenuId = $id;
        $this->isDeleteModalOpen = true;
    }

    public function closeDeleteModal()
    {
        $this->isDeleteModalOpen = false;
    }

    // 削除処理
    public function delete()
    {
         // ManagementServiceサービスに委譲
        $menus = $this->managementService->deleteMenu(
            $this->deleteMenuId
        );

        $this->deleteMenuId = null;
        $this->isDeleteModalOpen = false;
    }

    // ページネーション: 指定ページへ移動
    public function gotoPage($page)
    {
        $this->setPage($page);
    }

}