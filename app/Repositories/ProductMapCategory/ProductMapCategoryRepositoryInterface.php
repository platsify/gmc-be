<?php
namespace App\Repositories\ProductMapCategory;

use App\Repositories\RepositoryInterface;

interface ProductMapCategoryRepositoryInterface extends RepositoryInterface
{
    public function upsertByPici($pici, $attributes);
}
