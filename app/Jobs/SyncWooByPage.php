<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\ProductMapCategory;
use App\Models\Category;
use App\Repositories\Category\CategoryRepositoryInterface;
use App\Repositories\Product\ProductRepositoryInterface;
use App\Repositories\ProductMapCategory\ProductMapCategoryRepositoryInterface;
use App\Repositories\RawProduct\RawProductRepositoryInterface;
use Automattic\WooCommerce\Client;
use Carbon\Carbon;
use Doctrine\DBAL\Driver\Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncWooByPage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 0;
	public $tries = 2;

    private $page, $shop, $shopId, $categoryRepository;
    private $shopCategories;

     /** @var Client $object */
    private $woo;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($page, $shop, $shopId, $woo)
    {
        $this->page = $page;
        $this->shop = $shop;
        $this->shopId = $shopId;
        $this->woo = $woo;
        $this->shopCategories = $this->getShopCategories();
    }

    public function getShopCategories(): array
    {
        $rtCategories = array();
        $categories = Category::where('shop_id', (string)$this->shopId)->get()->toArray();
        foreach ($categories as $category) {
            $originalId = str_replace($this->shopId.'__', '', $category['original_id']);
            $rtCategories[$originalId] = $category;
        }
        return $rtCategories;
    }

    public function mapSbToProduct($sbProduct, $shop): array
    {
        $productData = array();
        $productData['name'] = $sbProduct->name;
        $productData['url'] = $sbProduct->permalink;
        $productData['image_url'] = !empty($sbProduct->images) ? $sbProduct->images[0]->src : '';
        $productData['original_id'] = $sbProduct->id;
        $productData['shop_id'] = $shop['id'];
        $productData['original_last_update'] = strtotime($sbProduct->date_modified);
        $productData['active'] = true;

        return $productData;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle(CategoryRepositoryInterface $categoryRepository, ProductRepositoryInterface $productRepository, ProductMapCategoryRepositoryInterface $productMapCategoryRepository, RawProductRepositoryInterface $rawProductRepository)
    {
        //echo 'Dang chay page ' .$this->page."\n";
        try {
            $wooProducts = $this->woo->get('products', ['status' => 'publish', 'page' => $this->page, 'per_page' => '10']);
        } catch (\Exception $e) {
            throw new \Exception($e);
        }

        if (!$wooProducts) {
           // echo "Ko co san pham nao\n";
            return;
        }

        $insertNewProductCount = 0;
        foreach ($wooProducts as $wooProduct) {
            $id = $wooProduct->id;
            $wooProduct = (object)$wooProduct;
            $wooProduct->isWooProduct = true;
            $wooProduct->shop_id = $this->shopId;
            $wooProduct->id = $this->shop->id . '__' . $wooProduct->id;
            $wooProduct->original_id = $wooProduct->id;
            $basicProductData = $this->mapSbToProduct($wooProduct, $this->shop);

			$allVariants = array();
			$variantPage = 0;
			do {
				$variantPage ++;
				$variants = $this->woo->get('products/' . $id . '/variations?per_page=100&page='.$variantPage);
				$allVariants = array_merge($allVariants, $variants);
				if (empty($variants)) {
					break;
				}
			} while(true);
			
            if (!empty($allVariants)) {
                foreach ($allVariants as &$variant) {
                    $variant->id =  $this->shop->id . '__' . $variant->id;
                }
                $wooProduct->variants = $allVariants;
            }
            // Ghi đè một số field nếu sản phẩm này đã tồn tại (có thể sẽ ko còn dùng default value nên cần ghi đè)
            $existingProduct = $productRepository->findBySpecificField('original_id', $basicProductData['original_id']);

            if ($existingProduct) {
                // Upsert raw product
                $rawProductRepository->upsertByProductId($existingProduct->id, (array)$wooProduct);

                // Upsert map product, category
                foreach ($wooProduct->categories as $category) {
                    if (!isset($this->shopCategories[$category->id])) {
                        continue;
                    }

                    $systemCategory = $this->shopCategories[$category->id];
                    $mapProductCategory = array('product_id' => $existingProduct->id, 'category_id' => $systemCategory['_id'], 'pici' => $existingProduct->id . '__' . $systemCategory['_id']);
                    $productMapCategoryRepository->upsertByPici($mapProductCategory['pici'], $mapProductCategory);
                }

                // Nếu ko có update gì thì next
                if (strtotime($wooProduct->date_modified) == $existingProduct->original_last_update) {
                    echo "Trùng " . $wooProduct->id . " \n";
                    continue;
                }
            } else {
                $insertNewProductCount++;
            }

            // Upsert product
            $savedProduct = $productRepository->upsertByOriginalId($basicProductData['original_id'], $basicProductData);

            // Upsert raw product
            $wooProduct->system_product_id = $savedProduct->id;
            $rawProductRepository->upsertByProductId($savedProduct->id, $wooProduct);

            // Upsert map product, category
            foreach ($wooProduct->categories as $category) {
                if (!isset($this->shopCategories[$category->id])) {
                   continue;
                }
                $systemCategory = $this->shopCategories[$category->id];
                $mapProductCategory = array('product_id' => $savedProduct->id, 'category_id' => $systemCategory['_id'], 'pici' => $savedProduct->id . '__' . $systemCategory['_id']);
                $productMapCategoryRepository->upsertByPici($mapProductCategory['pici'], $mapProductCategory);
            }

            // TODO: Upsert custom fields

            echo "Upsert product $wooProduct->id\n";
        }

        // TODO: Using ShopRepository
        // Update crawled count
        $this->shop->crawled_product = Product::where('shop_id', $this->shopId)->count();
        $this->shop->save();

        SyncWooByPage::dispatch($this->page + 5, $this->shop, $this->shopId, $this->woo);
    }
}
