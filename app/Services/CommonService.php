<?php

namespace App\Services;

class CommonService
{
    /**
     * モデルの全件取得（withリレーション対応）
     * @param string $modelClass モデルの完全修飾クラス名
     * @param array $with withで取得したいリレーション名配列
     * @return \Illuminate\Support\Collection
     */
    public function getAll(string $modelClass, array $with = [])
    {
        if (!empty($with)) {
            return $modelClass::with($with)->get();
        }
        return $modelClass::all();
    }

    public function transaction(callable $callback)
    {
        return \DB::transaction($callback);
    }
}
