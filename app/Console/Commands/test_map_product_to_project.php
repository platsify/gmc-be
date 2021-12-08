<?php

namespace App\Console\Commands;

use App\Jobs\MapProductToProject;
use App\Jobs\PushToGMC;
use App\Jobs\SyncShopbase;
use App\Models\Gmc;
use App\Models\ProductMapProjects;
use App\Models\ProductMapCategory;
use App\Models\Project;
use App\Models\RawProduct;
use App\Models\Shop;
use Illuminate\Console\Command;
use MOIREI\GoogleMerchantApi\Contents\Product\Product;
use MOIREI\GoogleMerchantApi\Contents\Product\ProductShipping;
use MOIREI\GoogleMerchantApi\Facades\ProductApi;

class test_map_product_to_project extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test_map {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $projectId = $this->argument('id');
		PushToGMC::dispatch($projectId);
        //MapProductToProject::dispatch($projectId, true);
        return;

        //SyncShopbase::dispatch($productId, true);
		
		$project = Project::where('_id', $projectId)->first();
        if (!$project) {
            echo 'Project not found';
            return;
        }
		
		$catIds = array();
		foreach ($project->categories as $cat) {
			$catIds[] = $cat['_id'];
		}
		
		$lastMap = 0;
		if ($project->lastMap) {
			$lastMap = $project->lastMap;
		}
		
        $productIds = ProductMapCategory::whereIn('category_id', $catIds)->where('original_last_update', '>', $lastMap)->pluck('product_id')->toArray();
		
        $rawProductsQuery = RawProduct::query();
        //$rawProductsQuery->select('shop_id', 'system_product_id');
        $rawProductsQuery->where('shop_id', $project->shop_id);
        $rawProductsQuery->whereIn('system_product_id', $productIds);

        //if ($project->require_gtin) {
        //    $rawProductsQuery->whereHas('variants', function($query) {
        //        $query->whereIn('barcode', '!=', '');
        //    });
        //}

        $rawProductsQuery->chunk(1000,  function($rawProducts) use($projectId) {
            //echo count($rawProducts);
            foreach ($rawProducts as $rawProduct) {
                $mapped = ProductMapProjects::where('product_id', $rawProduct->system_product_id)->where('project_id', $projectId)->first();
                if (!$mapped) {
                    ProductMapProjects::create([
                        'product_id' => $rawProduct->system_product_id,
                        'project_id' => $projectId,
                        'synced' => false,
                    ]);
                }
            }
        });
		
		$project->lastMap = time();
		$project->save();

        PushToGMC::dispatch($project->id);
    }
}
