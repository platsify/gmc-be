<?php
namespace App\Repositories\Product;

use App\Repositories\BaseRepository;

class ProductRepository extends BaseRepository implements ProductRepositoryInterface
{
    public function getModel()
    {
        return \App\Models\Product::class;
    }

    public function upsertByOriginalId($originalId, $attributes) {
        return $this->upsertBySpecificField('original_id', $originalId, $attributes);
    }

    public function getLastUpdateProduct()
    {
        return $this->model->orderBy('original_last_update', 'DESC')->first();
    }
}
