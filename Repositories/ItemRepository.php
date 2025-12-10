<?php

namespace Modules\SAS\Repositories;

use Modules\SAS\Entities\Item;
use Modules\SAS\Traits\RegisterTrait;
use Prettus\Repository\Eloquent\BaseRepository;

class ItemRepository extends BaseRepository
{
    use RegisterTrait;
    public function model()
    {
        return Item::class;
    }

    public function updateOrCreateItemInfo($item, $data) {
        $item->itemInfo()->updateOrCreate(['item_id' => $item->id], $data);
        return $item;
    }
}
