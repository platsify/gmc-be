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
        $productIds = ProductMapCategory::whereIn('category_id', $project->categories)->pluck('product_id')->toArray();
        $rawProductsQuery = RawProduct::query();
        //$rawProductsQuery->select('shop_id', 'system_product_id');
        $rawProductsQuery->where('shop_id', $project->shop_id);
        $rawProductsQuery->whereIn('system_product_id', $productIds);

        if ($project->require_gtin) {
            $rawProductsQuery->whereHas('variants', function($query) {
                $query->whereIn('barcode', '!=', '');
            });
        }

        $rawProductsQuery->chunk(1000,  function($rawProducts) {
            echo count($rawProducts);
            foreach ($rawProducts as $rawProduct) {
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

        PushToGMC::dispatch($project->id);
    }
}
