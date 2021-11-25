<?php

namespace App\Jobs;

use App\Models\Product;
use App\Repositories\Category\CategoryRepositoryInterface;
use App\Repositories\Product\ProductRepositoryInterface;
use App\Repositories\ProductMapCategory\ProductMapCategoryRepositoryInterface;
use App\Repositories\RawProduct\RawProductRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncShopebaseByCategory implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	public $timeout = 0;

    private $category, $lastSync, $shop, $shopId, $shopbase, $categoryRepository;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($category, $lastSync, $shop, $shopId, $shopbase)
    {
        $this->category = $category;
        $this->lastSync = $lastSync;
        $this->shop = $shop;
        $this->shopId = $shopId;
        $this->shopbase = $shopbase;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(CategoryRepositoryInterface $categoryRepository, ProductRepositoryInterface $productRepository, ProductMapCategoryRepositoryInterface $productMapCategoryRepository,  RawProductRepositoryInterface $rawProductRepository)
    {
        // Nếu quét lần đầu, thì sẽ rất nhiều SP, mà shopbase chỉ cho 10k sản phẩm phân trang, vì vậy chuyển sang quét theo id
        // TỪ lần sau, chỉ update lại, nên sẽ order by updated_at

        $sinceId = 0;
		if ($this->shopId == '619de0cafb2006073f1182b4') {
			$sinceId = '1000000283437106';
		}
		if ($this->shopId == '619de2e0efcd2331f96cc664') {
			$sinceId = '1000000206103931';
		}
        $lastUpdatedAt = $this->lastSync;
        $page = 0;

        do {
            $page++;
            $originalCategoryId = str_replace($this->shopId . '__', '', $this->category->original_id);
            if ($this->lastSync == 0) {
                $queryOptions = ['collection_id' => $originalCategoryId, 'limit' => 250, 'sort_field' => 'id', 'sort_mode' => 'asc', 'since_id' => $sinceId];
            } else {
                $queryOptions = ['collection_id' => $originalCategoryId, 'page' => $page, 'limit' => 250, 'updated_at_min' => Carbon::createFromTimestamp($lastUpdatedAt)->toISOString()];
            }
            $sbProducts = $this->shopbase->getProducts($queryOptions);
            if (!$sbProducts || empty($sbProducts) || empty($sbProducts->products)) {
                if (isset($sbProducts->error)) {
                    Log::error(json_encode($sbProducts));
                }
                break;
            }


            $insertNewProductCount = 0;
            foreach ($sbProducts->products as $sbProduct) {
                $sbProduct->shop_id = $this->shopId;
                $sinceId = $sbProduct->id;
                $sbProduct = (object)$sbProduct;
                $productData = $this->shopbase->mapSbToProduct($sbProduct, $this->shop);

                // Ghi đè một số field nếu sản phẩm này đã tồn tại (có thể sẽ ko còn dùng default value nên cần ghi đè)
                $existingProduct = $productRepository->findBySpecificField('original_id', $productData['original_id']);
                if ($existingProduct) {
                    // Upsert raw product
                    $rawProductRepository->upsertByProductId($existingProduct->id, $sbProduct);

                    // Upsert map product, category
                    $mapProductCategory = array('product_id' => $existingProduct->id, 'category_id' => $this->category->id, 'pici' => $existingProduct->id . '__' . $this->category->id);
                    $productMapCategoryRepository->upsertByPici($mapProductCategory['pici'], $mapProductCategory);


                    // Nếu ko có update gì thì next
                    if (strtotime($sbProduct->updated_at) == $existingProduct->original_last_update) {
                        echo "Trùng " . $sbProduct->id . " \n";
                        continue;
                    }
                } else {
                    $insertNewProductCount ++;
                }

                // Upsert product
                $savedProduct = $productRepository->upsertByOriginalId($productData['original_id'], $productData);

                // Upsert raw product
                $sbProduct->system_product_id = $savedProduct->id;
                $rawProductRepository->upsertByProductId($savedProduct->id, $sbProduct);

                // Upsert map product, category
                $mapProductCategory = array('product_id' => $savedProduct->id, 'category_id' => $this->category->id, 'pici' => $savedProduct->id . '__' . $this->category->id);
                $productMapCategoryRepository->upsertByPici($mapProductCategory['pici'], $mapProductCategory);


                // TODO: Upsert custom fields

                echo "Upsert product $sbProduct->id\n";
            }

            // TODO: Using ShopRepository
            // Update crawled count
            $this->shop->crawled_product = Product::where('shop_id', $this->shopId)->count();
            $this->shop->save();

        } while (true);
    }
}
