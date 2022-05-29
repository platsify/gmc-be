<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductMapCategory;
use App\Models\ProductMapProjects;
use App\Models\Project;
use App\Models\RawProduct;
use App\Models\Shop;
use Illuminate\Console\Command;

class CleanDB extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clean_db';

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
        $shopIds = Shop::get()->pluck('_id')->toArray();
        echo "Xoa category ". Category::whereNotIn('shop_id', $shopIds)->delete()."\n";
        echo "Xoa project ". Project::whereNotIn('shop_id', $shopIds)->delete() . "\n";

        $categoryIds = Category::get()->pluck('_id')->toArray();
        $projectIds = Project::get()->pluck('_id')->toArray();

        echo "Xoa product map category " . ProductMapCategory::whereNotIn('category_id', $categoryIds)->delete()."\n";
        echo "Xoa product map Project " . ProductMapProjects::whereNotIn('project_id', $projectIds)->delete() . "\n";

        echo "Xoa product " .Product::whereNotIn('shop_id', $shopIds)->delete()."\n";
        echo "Xoa product " .RawProduct::whereNotIn('shop_id', $shopIds)->delete()."\n";

        return 0;
    }
}
