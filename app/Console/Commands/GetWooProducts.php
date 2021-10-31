<?php

namespace App\Console\Commands;

use App\Services\WooClient;
use Illuminate\Console\Command;

class GetWooProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get_woo_products';

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
        $wc = new WooClient('https://ebookfiver.com/', 'ck_53767244239cb814ee141c454903129f80800b6f', 'cs_a04d6f2888c05d5ccb3035ba9edfb9dfa5ebeef3');
        dd($wc->getProducts());
        return Command::SUCCESS;
    }
}
