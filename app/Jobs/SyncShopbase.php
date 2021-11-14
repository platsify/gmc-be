<?php

namespace App\Jobs;

use App\Models\ProductMapCategory;
use App\Models\Shop;
use App\Repositories\Category\CategoryRepositoryInterface;
use App\Repositories\Product\ProductRepositoryInterface;
use App\Repositories\ProductMapCategory\ProductMapCategoryRepositoryInterface;
use App\Repositories\RawProduct\RawProductRepositoryInterface;
use App\Services\Shopbase;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncShopbase implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $lastSync;
    private $shopId;

    /** @var Shop $object */
    private $shop;

    private $categoryRepository;
    private $productRepository;
    private $productMapCategoryRepository;
    private $rawProductRepository;

    /** @var Shopbase $object */
    private $shopbase;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($shopId, $lastSync = 0)
    {
        $this->lastSync = $lastSync;
        $this->shopId = $shopId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(CategoryRepositoryInterface $categoryRepository, ProductRepositoryInterface $productRepository, ProductMapCategoryRepositoryInterface $productMapCategoryRepository,  RawProductRepositoryInterface $rawProductRepository)
    {
        $this->categoryRepository = $categoryRepository;
        $this->productRepository = $productRepository;
        $this->productMapCategoryRepository = $productMapCategoryRepository;
        $this->rawProductRepository = $rawProductRepository;

        $shop = Shop::where('_id', $this->shopId)->where('type', Shop::SHOP_TYPE_SHOPBASE)->first();
        if (!$shop) {
            echo 'Shop not found';
            return;
        }

        if (!$shop->active) {
            return;
        }

        $this->shop = $shop;

        // TODO: Using ShopRepository
        // Lưu trạng thái job đang chạy
        $this->shop->sync_status = Shop::SHOP_SYNC_RUNNING;
        $this->shop->save();


        $this->shopbase = new Shopbase($shop->url, $shop->api_key, $shop->api_secret);
        $this->syncCategories();
        $this->countProducts();
        $this->syncProducts();

        // TODO: Using ShopRepository
        // Save done job and last sync
        $this->shop->sync_status = Shop::SHOP_SYNC_DONE;
        $this->shop->last_sync = time();
        $this->shop->save();
    }

    public function countProducts() {
        $sbCountProduct = $this->shopbase->countProducts();
        if ($sbCountProduct && !empty($sbCountProduct->count)) {
            // TODO: Using ShopRepository
            $this->shop->total_product = $sbCountProduct->count;
            $this->shop->save();
        }
    }

    public function syncProducts()
    {
        $categories = $this->categoryRepository->findManyBySpecificField('shop_id', $this->shopId);
        foreach ($categories as $category) {
            // Nếu quét lần đầu, thì sẽ rất nhiều SP, mà shopbase chỉ cho 10k sản phẩm phân trang, vì vậy chuyển sang quét theo id
            // TỪ lần sau, chỉ update lại, nên sẽ order by updated_at

            $sinceId = 0;
            $lastUpdatedAt = $this->lastSync;
            $page = 0;

            do {
                $page++;
                $originalCategoryId = str_replace($this->shopId . '__', '', $category->original_id);
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
                    $existingProduct = $this->productRepository->findBySpecificField('original_id', $productData['original_id']);
                    if ($existingProduct) {
                        // Upsert raw product
                        $this->rawProductRepository->upsertByProductId($existingProduct->id, $sbProduct);

                        // Upsert map product, category
                        $mapProductCategory = array('product_id' => $existingProduct->id, 'category_id' => $category->id, 'pici' => $existingProduct->id . '__' . $category->id);
                        $this->productMapCategoryRepository->upsertByPici($mapProductCategory['pici'], $mapProductCategory);


                        // Nếu ko có update gì thì next
                        if (strtotime($sbProduct->updated_at) == $existingProduct->original_last_update) {
                            echo "Trùng " . $sbProduct->id . " \n";
                            continue;
                        }
                    } else {
                        $insertNewProductCount ++;
                    }

                    // Upsert product
                    $savedProduct = $this->productRepository->upsertByOriginalId($productData['original_id'], $productData);

                    // Upsert raw product
                    $sbProduct->system_product_id = $savedProduct->id;
                    $this->rawProductRepository->upsertByProductId($savedProduct->id, $sbProduct);

                    // Upsert map product, category
                    $mapProductCategory = array('product_id' => $savedProduct->id, 'category_id' => $category->id, 'pici' => $savedProduct->id . '__' . $category->id);
                    $this->productMapCategoryRepository->upsertByPici($mapProductCategory['pici'], $mapProductCategory);


                    // TODO: Upsert custom fields

                    echo "Upsert product $sbProduct->id\n";
                }

                // TODO: Using ShopRepository
                // Update crawled count
                $crawledProductCount = $this->shop->crawled_product?: 0;
                $this->shop->crawled_product = $crawledProductCount + $insertNewProductCount;
                $this->shop->save();

            } while (true);
        }
    }

    public function syncCategories()
    {
        $categories = array();
        $customCollections = $this->shopbase->getCustomCollections();
        $smartCollections = $this->shopbase->getSmartCollections();

        if ($customCollections && !empty($customCollections->custom_collections)) {
            foreach ($customCollections->custom_collections as $collection) {

                $collection = (object)$collection;
                $categories[] = array(
                    'shop_id' => $this->shopId,
                    'active' => $collection->published,
                    'original_id' => $this->shopId . '__' . $collection->id,
                    'name' => $collection->title
                );
            }
        }

        if ($smartCollections && !empty($smartCollections->smart_collections)) {
            foreach ($smartCollections->smart_collections as $collection) {
                $collection = (object)$collection;
                $categories[] = array(
                    'shop_id' => $this->shopId,
                    'active' => $collection->published,
                    'original_id' => $this->shopId . '__' . $collection->id,
                    'name' => $collection->title
                );
            }
        }

        // Upsert category
        foreach ($categories as $category) {
            $this->categoryRepository->upsertByOriginalId($category['original_id'], $category);
        }
    }
}
