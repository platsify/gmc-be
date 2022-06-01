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

class FindMissingProduct extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'find_missing_product';

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
        $ids = json_decode(file_get_contents('id2.txt'));
		
		$newIds = array();
		Product::where('shop_id', '6295e6c0cef5d547605261c7')->chunk(1000, function($products) use (&$newIds) {
			foreach($products AS $product) {
				$id = str_replace('6295e6c0cef5d547605261c7__', '', $product->original_id);
				$newIds[] = $id;
			}
		});
		sort($ids);
		sort($newIds);
		file_put_contents('id5.txt', implode(',', array_diff($ids, $newIds)));
		//file_put_contents('id4.txt', implode(PHP_EOL, $newIds));
    }
}
