<?php

namespace App\Jobs;

use App\Models\ProductMapCategory;
use App\Models\Shop;
use App\Repositories\Category\CategoryRepositoryInterface;
use App\Repositories\Product\ProductRepositoryInterface;
use App\Repositories\ProductMapCategory\ProductMapCategoryRepositoryInterface;
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

    private $isFirstCrawlTime;
    private $shopId;

    /** @var Shop $object */
    private $shop;

    private $categoryRepository;
    private $productRepository;
    private $productMapCategoryRepository;

    /** @var Shopbase $object */
    private $shopbase;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($shopId, $isFirstCrawlTime = true)
    {
        $this->isFirstCrawlTime = $isFirstCrawlTime;
        $this->shopId = $shopId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(CategoryRepositoryInterface $categoryRepository, ProductRepositoryInterface $productRepository, ProductMapCategoryRepositoryInterface $productMapCategoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
        $this->productRepository = $productRepository;
        $this->productMapCategoryRepository = $productMapCategoryRepository;

        $shop = Shop::find($this->shopId)->where('type', Shop::SHOP_TYPE_SHOPBASE)->first();
        if (!$shop) {
            return;
        }
        $this->shop = $shop;
        $this->shopbase = new Shopbase($shop->url, $shop->api_key, $shop->api_secret);

        $this->syncCategories();

        $this->syncProducts();
    }

    public function syncProducts() {
        $categories = $this->categoryRepository->findManyBySpecificField('shop_id', $this->shopId);
        foreach ($categories as $category) {
            // Nếu quét lần đầu, thì sẽ rất nhiều SP, mà shopbase chỉ cho 10k sản phẩm phân trang, vì vậy chuyển sang quét theo id
            // TỪ lần sau, chỉ update lại, nên sẽ order by updated_at

            $sinceId = 0;
            $lastUpdatedAt = 0;
            $page = 0;

            $lastUpdateProduct = $this->productRepository->getLastUpdateProduct();
            if ($lastUpdateProduct) {
                $lastUpdatedAt = $lastUpdateProduct->original_last_update;
            }

            do {
                $page++;
                $originalCategoryId = str_replace($this->shopId.'__', '', $category->original_id);
                if ($this->isFirstCrawlTime) {
                    $queryOptions = ['collection_id' => $originalCategoryId, 'limit' => 250, 'sort_field' => 'id', 'sort_mode' => 'asc', 'since_id' => $sinceId];
                } else {
                    $queryOptions = ['collection_id' => $originalCategoryId, 'page' => $page, 'limit' => 250, 'sort_field' => 'updated_at', 'sort_mode' => 'asc', 'updated_at_min' => Carbon::createFromTimestamp($lastUpdatedAt)->toISOString()];
                }
                $sbProducts = $this->shopbase->getProducts($queryOptions);
                if (!$sbProducts || empty($sbProducts) || empty($sbProducts->products)) {
                    if (isset($sbProducts->error)) {
                        Log::error(json_encode($sbProducts));
                    }
                    break;
                }

                foreach ($sbProducts->products as $sbProduct) {

                    $sinceId = $sbProduct->id;
                    $sbProduct = (object)$sbProduct;
                    $productData = $this->shopbase->mapSbToProduct($sbProduct, $this->shop);

                    // Ghi đè một số field nếu sản phẩm này đã tồn tại (có thể sẽ ko còn dùng default value nên cần ghi đè)
                    $existingProduct = $this->productRepository->findBySpecificField('original_id', $productData['original_id']);
                    if ($existingProduct) {

                        // Upsert map product, category
                        $mapProductCategory = array('product_id' => $existingProduct->id, 'category_id' => $category->id, 'pici' => $existingProduct->id.'__'.$category->id);
                        $this->productMapCategoryRepository->upsertByPici($mapProductCategory['pici'], $mapProductCategory);


                        // Nếu ko có update gì thì next
                        if (strtotime($sbProduct->updated_at) == $existingProduct->original_last_update) {
                            echo "Trùng " . $sbProduct->id . " \n";
                            continue;
                        }
                        $productData['sync_gmc'] = $existingProduct->sync_gmc;
                    }

                    // Upsert product
                    $savedProduct = $this->productRepository->upsertByOriginalId($sbProduct->id, $productData);

                    // Upsert map product, category
                    $mapProductCategory = array('product_id' => $savedProduct->id, 'category_id' => $category->id, 'pici' => $savedProduct->id.'__'.$category->id);
                    $this->productMapCategoryRepository->upsertByPici($mapProductCategory['pici'], $mapProductCategory);


                    // TODO: Upsert custom fields

                    echo "Upsert product $sbProduct->id\n";
                }
            } while (true);
        }
    }

    public function syncCategories() {
        $categories = array();
        $customCollections = $this->shopbase->getCustomCollections();
        $smartCollections = $this->shopbase->getSmartCollections();

        if ($customCollections && !empty($customCollections->custom_collections)) {
            foreach ($customCollections->custom_collections as $collection) {

                $collection = (object) $collection;
                $categories[] = array(
                    'shop_id' => $this->shopId,
                    'active' => $collection->published,
                    'original_id' => $this->shopId.'__'.$collection->id,
                    'name' => $collection->title
                );
            }
        }

        if ($smartCollections && !empty($smartCollections->smart_collections)) {
            foreach ($smartCollections->smart_collections as $collection) {
                $collection = (object) $collection;
                $categories[] = array(
                    'shop_id' => $this->shopId,
                    'active' => $collection->published,
                    'original_id' => $this->shopId.'__'.$collection->id,
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
