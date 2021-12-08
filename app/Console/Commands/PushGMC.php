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

class PushGMC extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'push_gmc';

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
		PushToGMC::dispatch();
        return;
    }
}
