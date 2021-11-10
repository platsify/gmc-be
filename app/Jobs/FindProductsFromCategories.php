<?php

namespace App\Jobs;

use App\Models\ProductMapCategory;
use App\Models\ProductMapProjects;
use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FindProductsFromCategories implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $projectId;
    private $categories;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($projectId, $categories)
    {
        $this->projectId = $projectId;
        $this->categories = $categories;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $categories = $this->categories;
        $productIds = ProductMapCategory::whereIn('category_id', $categories)->pluck('product_id')->toArray();
        foreach ($productIds as $productId) {
            ProductMapProjects::create([
                'product_id' => $productId,
                'project_id' => $this->projectId
            ]);
        }
    }
}
