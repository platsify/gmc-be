<?php

namespace Database\Seeders;

use App\Models\Shop;
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
//        DB::table('shops')->insert([
//            'name' => 'dathoastore',
//            'url' => 'https://dathoastore.onshopbase.com/',
//            'public_url' => 'https://outdoorprints3d.com/',
//            'type' => 1,
//            'gmc_id' => '510563015',
//            'gmc_credential' => 'app/google-merchant-api/tran-hoang-giang.json',
//            'api_key' => 'c8e63653a5c3a3016683a5ca45516c58',
//            'api_secret' => 'd24febea58d2444e8deec05cb26c6995b3080c7191e26a2b51a227bb92c12231',
//            'active' => true,
//            'total_product' => 0,
//            'crawled_product' => 0,
//            'last_sync' => 0,
//            'sync_status' => Shop::SHOP_SYNC_NEVER,
//        ]);

        DB::table('shops')->insert([
            'name' => 'cvsunny',
            'url' => 'https://thelongstore.onshopbase.com/',
            'public_url' => 'https://www.cvsunnyday.com/',
            'type' => 1,
            'gmc_id' => '510563015',
            'gmc_credential' => 'app/google-merchant-api/tran-hoang-giang.json',
            'api_key' => '41bbb2bb3e5d87c7d93e04b02559469c',
            'api_secret' => 'b8435dd14b1ea8c66662ed1f3c6824183c043c7348e1451c8260510aa9d2120c',
            'active' => true,
            'total_product' => 0,
            'crawled_product' => 0,
            'last_sync' => 0,
            'sync_status' => Shop::SHOP_SYNC_NEVER,
        ]);
    }
}
