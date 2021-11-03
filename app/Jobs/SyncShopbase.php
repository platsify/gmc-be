<?php

namespace App\Jobs;

use App\Models\Shop;
use App\Repositories\Category\CategoryRepositoryInterface;
use App\Services\Shopbase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncShopbase implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $shopId;

    private $categoryRepository;

    /** @var Shopbase $object */
    private $shopbase;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($shopId)
    {
        $this->shopId = $shopId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(CategoryRepositoryInterface $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;

        $shop = Shop::find($this->shopId)->where('type', Shop::SHOP_TYPE_SHOPBASE)->first();
        if (!$shop) {
            return;
        }
        $this->shopbase = new Shopbase($shop->url, $shop->api_key, $shop->api_secret);

        $this->syncCategories();
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
                    'original_id' => $collection->id,
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
                    'original_id' => $collection->id,
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
