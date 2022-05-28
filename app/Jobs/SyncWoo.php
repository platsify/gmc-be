<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\ProductMapCategory;
use App\Models\Shop;
use App\Repositories\Category\CategoryRepositoryInterface;
use App\Repositories\Product\ProductRepositoryInterface;
use App\Repositories\ProductMapCategory\ProductMapCategoryRepositoryInterface;
use App\Repositories\RawProduct\RawProductRepositoryInterface;
use App\Services\WooGApi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncWoo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	public $timeout = 0;


    private $lastSync;
    private $shopId;

    /** @var WooGApi $object */
    private $woo;

    /** @var Shop $object */
    private $shop;

    private $categoryRepository;
    private $productRepository;
    private $productMapCategoryRepository;
    private $rawProductRepository;
    private $connectionName;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($shopId, $lastSync = 0)
    {
        $this->lastSync = $lastSync;
        $this->shopId = $shopId;
        $this->connectionName = 'woo_shop_'.$shopId;
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

        $shop = Shop::where('_id', $this->shopId)->where('type', Shop::SHOP_TYPE_WOO)->first();
        if (!$shop) {
            echo 'Shop not found';
            return;
        }

        if (!$shop->active) {
            return;
        }

        $this->shop = $shop;

        $this->initWooClient();

        $this->syncCategories();
        $this->syncProducts();

        // TODO: Using ShopRepository
        // Save done job and last sync
//        $this->shop->sync_status = Shop::SHOP_SYNC_DONE;
//        $this->shop->last_sync = time();
//        $this->shop->save();
    }

    public function syncProducts() {
        for ($i = 1; $i < 11; $i++) {
            SyncWooByPage::dispatch($i, $this->shop, $this->shopId, $this->woo);
        }
    }

    public function initWooClient() {
        $this->woo = new WooGApi(
            $this->shop->public_url,
            $this->shop->api_key
        );
    }
    public function syncCategories()
    {
        $categories = array();
        $items = $this->woo->getRequest(['r' => 'categories']);
        if (!empty($items)) {
            foreach ($items as $collection) {
                $collection = (object)$collection;
                $categories[] = array(
                    'shop_id' => $this->shopId,
                    'active' => true,
                    'original_id' => $this->shopId . '__' . $collection->id,
                    'name' => $collection->name
                );
            }
        }

        // Upsert category
        foreach ($categories as $category) {
            $this->categoryRepository->upsertByOriginalId($category['original_id'], $category);
        }
    }
}
