<?php

namespace App\Jobs;

use App\Models\Product;
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

	public $timeout = 0;


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
//        $this->shop->sync_status = Shop::SHOP_SYNC_DONE;
//        $this->shop->last_sync = time();
//        $this->shop->save();
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
            SyncShopebaseByCategory::dispatch($category, $this->lastSync, $this->shop, $this->shopId, $this->shopbase);
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
