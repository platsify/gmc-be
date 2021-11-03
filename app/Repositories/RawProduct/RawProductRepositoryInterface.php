<?php
namespace App\Repositories\RawProduct;

use App\Repositories\RepositoryInterface;

interface RawProductRepositoryInterface extends RepositoryInterface
{
    public function upsertByProductId($productId, $attributes);
}
