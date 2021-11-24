<?php

namespace App\Jobs;

use App\Models\ProductMapCategory;
use App\Models\ProductMapProjects;
use App\Models\Project;
use App\Models\RawProduct;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MapProductToProject implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
	
	public $timeout = 0;
	
    private $projectId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($projectId)
    {
        $this->projectId = $projectId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $project = Project::where('_id', $this->projectId)->first();
        if (!$project) {
            echo 'Project not found';
            return;
        }

        $catIds = [];
        foreach($project->categories AS $category) {
            $catIds[] = $category['_id'];
        }
        $productIds = ProductMapCategory::whereIn('category_id', $catIds)->pluck('product_id')->toArray();
        $rawProductsQuery = RawProduct::query();
        //$rawProductsQuery->select('shop_id', 'system_product_id');
        $rawProductsQuery->where('shop_id', $project->shop_id);
        $rawProductsQuery->whereIn('system_product_id', $productIds);

        // if ($project->require_gtin) {
        //     $rawProductsQuery->whereHas('variants', function($query) {
        //         $query->whereIn('barcode', '!=', '');
        //     });
        // }


        $rawProductsQuery->chunk(1000,  function($rawProducts) use ($project) {
            foreach ($rawProducts as $rawProduct) {
                // TODO: Xu ly san pham ko co variation
                if (!$rawProduct->variants) {
                    continue;
                }
                $foundGtin = false;
                foreach($rawProduct->variants AS $variation) {
                    //  Điều kiện lọc
                    if ($project->require_gtin && !empty($variant->barcode)) {
                        $foundGtin = true;
                        break;
                    }
                }
                if ($project->require_gtin && !$foundGtin) {
                    echo 'Project nay yeu cau GTIN nhung variation nay lai ko co barcode';
                    continue;
                }

                $mapped = ProductMapProjects::where('product_id', $rawProduct->system_product_id)->where('project_id', $this->projectId)->first();
                if (!$mapped) {
                    ProductMapProjects::create([
                        'product_id' => $rawProduct->system_product_id,
                        'project_id' => $this->projectId,
                        'synced' => false,
                    ]);
                }
            }
        });

        echo 'Tao job push GMC';
        PushToGMC::dispatch($project->id)->onQueue('gmc');
    }
}
