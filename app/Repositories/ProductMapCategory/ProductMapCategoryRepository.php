<?php
namespace App\Repositories\ProductMapCategory;

use App\Repositories\BaseRepository;

class ProductMapCategoryRepository extends BaseRepository implements ProductMapCategoryRepositoryInterface
{
    public function getModel()
    {
        return \App\Models\ProductMapCategory::class;
    }

    public function upsertByPici($pici, $attributes) {
        return $this->upsertBySpecificField('pici', $pici, $attributes);
    }
}
