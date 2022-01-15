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
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncWoo implements ShouldQueue
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

        $shop = Shop::where('_id', $this->shopId)->where('type', Shop::SHOP_TYPE_SHOPBASE)->first();
        if (!$shop) {
            echo 'Shop not found';
            return;
        }

        if (!$shop->active) {
            return;
        }

        $this->shop = $shop;

        $this->initWooConnection();

        $this->syncCategories();

        // TODO: Using ShopRepository
        // Save done job and last sync
//        $this->shop->sync_status = Shop::SHOP_SYNC_DONE;
//        $this->shop->last_sync = time();
//        $this->shop->save();
    }

    public function initWooConnection() {
        Config::set("database.connections.".$this->connectionName, [
            'driver' => 'mysql',
            "host" => $this->shop->dbHost,
            "database" => $this->shop->dbName,
            "username" => $this->shop->dbUserName,
            "password" => $this->shop->dbPass,
            "port" => $this->shop->dbPort,
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => $this->shop->dbPrefix,
            'strict'    => false,
        ]);
    }
    public function syncCategories()
    {
        $categories = array();
        $termTaxonomies = DB::connection($this->connectionName)->select('SELECT * FROM term_taxonomy WHERE taxonomy = "product_cat"');
        $termIds = $termTaxonomies->pluck('term_id')->toArray();

        $termIdsString = implode(',', $termIds);
        $terms = DB::connection($this->connectionName)->select('SELECT * FROM terms WHERE term_id IN('.$termIdsString.')');
        if ($terms && !empty($terms)) {
            foreach ($terms as $collection) {
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
