<?php

namespace App\Console\Commands;

use App\Jobs\SyncShopbase;
use App\Models\Shop;
use App\Services\Shopbase;
use Illuminate\Console\Command;

class SyncShopbaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync_shopbase';

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
        SyncShopbase::dispatch(1);
    }

}
