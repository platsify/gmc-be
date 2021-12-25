<?php

namespace App\Console\Commands;

use App\Jobs\SyncShopbase;
use App\Models\Shop;
use App\Models\VariantWhitelist;
use App\Services\Shopbase;
use Illuminate\Console\Command;

class AddVariantWhitelist extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add_variant_whitelist';

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
        $lines = explode(PHP_EOL, file_get_contents('ids.txt'));
        foreach ($lines as $line) {
            $variantWhitelist = new VariantWhitelist();
            $variantWhitelist->variant_id = $line;
            $variantWhitelist->save();
            echo $variantWhitelist->variant_id . "\n";
        }
        echo 'DONE';
    }

}
