<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use DB;


class SyncWoo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sc';

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
        $db = 'genterbo_0001';
        $user = 'genterbo_genterbook2021';
        $pass = 'aRd354H~TW5g';
        $host = '45.148.121.22';
        Config::set("database.connections.genterbook", [
            'driver' => 'mysql',
            "host" => $host,
            "database" => $db,
            "username" => $user,
            "password" => $pass,
            "port" => '3306',
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
            'strict'    => false,
        ]);

        $categories = DB::connection('genterbook')->select('SELECT * FROM t4lwqw_term_taxonomy WHERE taxonomy = "product_cat"');
        foreach ($categories as $category) {
            echo $category->term_id;
            echo '<br>';
        }
        return Command::SUCCESS;
    }
}
