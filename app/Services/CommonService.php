<?php

namespace App\Services;

class CommonService
{
    //モデルの全件取得（withリレーション対応）
    public function getAll(string $modelClass, array $with = [])
    {
        if (!empty($with)) {
            return $modelClass::with($with)->get();
        }
        return $modelClass::all();
    }

    //指定したIDのモデルをロック付きで取得
    public function findWithLock(string $modelClass, int $id)
    {
        return $modelClass::where('id', $id)->lockForUpdate()->firstOrFail();
    }

    //モデルの全件取得（リレーション付き）
    public function getAllWithRelations(string $modelClass, array $relations = [])
    {
        return $modelClass::with($relations);
    }

    //トランザクション処理
    public function transaction(callable $callback)
    {
        return \DB::transaction($callback);
    }
}
