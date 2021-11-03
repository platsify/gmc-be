<?php
namespace App\Repositories\RawProduct;

use App\Repositories\BaseRepository;

class RawProductRepository extends BaseRepository implements RawProductRepositoryInterface
{
    public function getModel()
    {
        return \App\Models\RawProduct::class;
    }

    public function upsertByProductId($productId, $attributes) {
        return $this->upsertBySpecificField('product_id', $productId, $attributes);
    }
}
