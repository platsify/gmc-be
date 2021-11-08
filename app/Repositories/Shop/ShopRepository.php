<?php

namespace App\Repositories\Shop;

use App\Models\Category;
use App\Repositories\BaseRepository;

class ShopRepository  extends BaseRepository implements ShopRepositoryInterface
{
    public function getModel()
    {
        return \App\Models\Shop::class;
    }
}
