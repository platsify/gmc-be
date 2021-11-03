<?php

namespace App\Repositories\Category;

use App\Models\Category;
use App\Repositories\BaseRepository;

class CategoryRepository  extends BaseRepository implements CategoryRepositoryInterface
{
    public function getModel()
    {
        return \App\Models\Category::class;
    }

    public function upsertByOriginalId($originalId, $attributes) {
       return $this->upsertBySpecificField('original_id', $originalId, $attributes);
    }
}
