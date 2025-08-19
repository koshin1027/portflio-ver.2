<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Category;
use App\Models\Menu;
use App\Services\ManagementService;

class Management extends Component

{
    //ページネーショントレイト
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
    
    //追加
    public $isAddModalOpen = false;

    //編集
    public $isEditModalOpen = false;
    public $editMenuId;

    //削除
    public $isDeleteModalOpen = false;
    public $deleteMenuId;

    // 商品情報プロパティ
    public $name = '';
    public $price = 0;
    public $category_id = '';
    public $status = '';
    public $amount = 0;
    public $explanation = '';

    //依存性注入
    public function boot(ManagementService $managementService)
    {
        $this->managementService = $managementService;
    }

    //初期化
    public function mount()
    {
        //DBからカテゴリーを全取得
        $this->categories = Category::all();

        //取得したデータから最初のカテゴリーを$activeCategoryIdに格納
        $this->activeCategoryId = $this->categories->first()->id;
    }

    //描画ごとの処理
    public function render()
    {
        //managementServiceから「メニューのフィルタリング機能」を移譲
        $menus = $this->managementService->searchMenus(
            $this->activeCategoryId,
            $this->search,
            $this->filterStatus,
            $this->sortPrice
        );

        //フィルタリングされたメニューをlivewire.management.bladeに渡す
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
    }

    //選択されたカテゴリーをアクティブカテゴリーにセット
    public function setActiveCategory($categoryId)
    {
        $this->activeCategoryId = $categoryId;

        //カテゴリーが変わるたび検索欄を空白にリセット
        $this->search = '';
    }

    // 注文追加用モーダルの制御処理

    // オープン
    public function openAddModal()
    {
        //オープン前にフォームの値をすべてリセット
        $this->resetForm();

        $this->isAddModalOpen = true;
    }

    //クローズ
    public function closeAddModal()
    {
        $this->isAddModalOpen = false;
    }

    // メニュー追加処理(追加ボタン押下時)
    public function submit()
    {
        // managementServiceから「注文を追加する機能」を移譲
            $menus = $this->managementService->createMenu(
                $this->name,
                $this->price,
                $this->category_id,
                $this->status,
                $this->amount,
                $this->explanation,
            );

        //フォームリセット
        $this->resetForm();

        //モーダル閉じる
        $this->isAddModalOpen = false;
    }

    // 注文編集用モーダルの制御処理

    public function openEditModal($id)
    {
        //対象のIDを参照して$selectMenusに格納
        $this->selectMenus = Menu::find($id);

        //フィールドを定義
        $fields = ['name', 'price', 'category_id', 'status', 'amount', 'explanation'];

        //$selectMenusの情報を分割代入
        foreach ($fields as $field) {
            $this->$field = $this->selectMenus->$field;
        }

        //対象IDを$editMenuIdに格納
        $this->editMenuId = $id;

        //注文編集用モーダルをオープン
        $this->isEditModalOpen = true;
    }

    // アップデート処理

    public function update()
    {
    //現在のプロパティを参照し、更新用のデータを定義
    $data = [
        'name' => $this->name,
        'price' => $this->price,
        'category_id' => $this->category_id,
        'status' => $this->status,
        'amount' => $this->amount,
        'explanation' => $this->explanation,
    ];

    //managementServiceから「注文を追加する機能」を移譲
    $menus = $this->managementService->updateMenu($this->selectMenus, $data);

        //閉じる前にフォームリセット
        $this->resetForm();

        //モーダルを閉じる
        $this->isEditModalOpen = false;
    }

    //「X」ボタン押下時用のクローズ処理
    public function closeEditModal()
    {
        $this->isEditModalOpen = false;
    }

    // 削除用モーダルの制御処理
    
    //オープン
    public function openDeleteModal($id)
    {
        //対象のIDを$deleteMenuIdに格納
        $this->deleteMenuId = $id;
        $this->isDeleteModalOpen = true;
    }

    //クローズ
    public function closeDeleteModal()
    {
        $this->isDeleteModalOpen = false;
    }

    // 削除処理
    public function delete()
    {
         // managementServiceから「メニューを削除する機能」を移譲
        $menus = $this->managementService->deleteMenu(
            $this->deleteMenuId
        );

        //$deleteMenuIdをリセット
        $this->deleteMenuId = null;

        //クローズ
        $this->isDeleteModalOpen = false;
    }

    // ページネーション
    public function gotoPage($page)
    {
        $this->setPage($page);
    }

}
