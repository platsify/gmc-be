<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShopSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('shops')->insert([
            'name' => 'dathoastore',
            'url' => 'https://dathoastore.onshopbase.com/',
            'type' => 1,
            'gmc_id' => '510563015',
            'gmc_credential' => 'app/google-merchant-api/tran-hoang-giang.json',
            'api_key' => 'c8e63653a5c3a3016683a5ca45516c58',
            'api_secret' => 'd24febea58d2444e8deec05cb26c6995b3080c7191e26a2b51a227bb92c12231',
            'active' => true
        ]);
    }
}
