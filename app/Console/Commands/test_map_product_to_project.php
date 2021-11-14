<?php

namespace App\Console\Commands;

use App\Jobs\MapProductToProject;
use App\Jobs\PushToGMC;
use App\Models\Gmc;
use App\Models\ProductMapProjects;
use App\Models\Project;
use App\Models\RawProduct;
use App\Models\Shop;
use Illuminate\Console\Command;
use MOIREI\GoogleMerchantApi\Contents\Product\Product;
use MOIREI\GoogleMerchantApi\Contents\Product\ProductShipping;
use MOIREI\GoogleMerchantApi\Facades\ProductApi;

class test_map_product_to_project extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test_map';

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
        //MapProductToProject::dispatch('618d4588656c00005b00064d');
        //return;
        PushToGMC::dispatch('618d4588656c00005b00064d');
//
//        $project = Project::find('618d4588656c00005b00064d');
//        if (!$project) {
//            return;
//        }
//
//        $shop = Shop::find($project->shop_id);
//        if (!$shop) {
//            return;
//        }
//
//        // Fill default value for product
//        $defaultValues = array();
//        foreach ($project->default_values as $default_value) {
//            $keyName = $default_value['name'];
//            $keyValue = $default_value['value'];
//            if (empty($rawProduct->{$keyName})) {
//                $defaultValues[$keyName] = $keyValue;
//            }
//        }
//
//        ProductMapProjects::where('project_id', $project->id)->where('synced', false)->chunk(10, function ($maps) use ($project, $shop) {
//            foreach ($maps as $map) {
//                $rawProduct = RawProduct::where('system_product_id', $map->product_id)->first();
//                if (!$rawProduct) {
//                    return;
//                }
//
//
//                // Các field có thể ghi đè
//                //Adult, Gender, shipping_price, ageGroup, color
//                $shippingPrice = 5.05;
//                $adult = false;
//                $gender = 'unisex';
//                $ageGroup = 'adult';
//                $color = 'multicolor';
//                $size = 'Free size';
//                if (isset($defaultValues['adults'])) {
//                    $adult = (boolean)$defaultValues['adults'];
//                }
//                if (isset($defaultValues['gender'])) {
//                    $gender = $defaultValues['gender'];
//                }
//                if (isset($defaultValues['shipping_price'])) {
//                    $shippingPrice = $defaultValues['shipping_price'];
//                }
//                if (isset($defaultValues['ageGroup'])) {
//                    $ageGroup = $defaultValues['ageGroup'];
//                }
//
//                // Tìm số thự tự của color và size trong options
//                $colorOption = 2;
//                $sizeOption = 1;
//                foreach ($rawProduct->options as $item) {
//                    $item = (object) $item;
//                    if ($item->name == 'Size') {
//                        $sizeOption = $item->position;
//                    }
//                    if ($item->name == 'Color') {
//                        $colorOption = $item->position;
//                    }
//                }
//
//                foreach ($rawProduct->variants as $variant) {
//                    $variant = (object) $variant;
//
//                    //  Điều kiện lọc
//                    if ($project->require_gtin && empty($variant->barcode)) {
//                        continue;
//                    }
//
//                    if (!empty($project->only_option1) && (!isset($variant->option1) || $variant->option1 != $project->only_option1)) {
//                        //echo 'Option 1 not match';
//                        continue;
//                    }
//                    if (!empty($project->only_option2) && (!isset($variant->option2) || $variant->option2 != $project->only_option2)) {
//                        //echo 'Option 2 not match';
//                        continue;
//                    }
//                    if (!empty($project->only_option3) && (!isset($variant->option3) || $variant->option3 != $project->only_option3)) {
//                        continue;
//                    }
//
//                    // Thay thế default value
//                    if (!empty($variant->{'option'.$sizeOption})) {
//                        $size = $variant->{'option'.$sizeOption};
//                    }
//                    if (!empty($variant->{'option'.$colorOption})) {
//                        $color = $variant->{'option'.$colorOption};
//                    }
//
//                    // Map vào GMC
//                    $gmcData = new Product();
//                    $gmcData->ageGroup($ageGroup);
//                    $gmcData->color($color);
//                    $gmcData->sizes($size);
//                    $gmcData->gender($gender);
//                    $gmcData->adult($adult);
//                    $gmcData->title($variant->title);
//                    $gmcData->description($rawProduct->body_html);
//                    //$gmcData->id($gmcData->channel . ':'.$gmcData->contentLanguage.':'.$gmcData->targetCountry.':'.$gmcData->offerId);
//                    $gmcData->link(rtrim( $shop->public_url, '/').'/'.$rawProduct->handle);
//                    $gmcData->image($rawProduct->image['src']);
//                    $gmcData->lang('en');
//                    $gmcData->country('us');
//                    $gmcData->online('online');
//                    $gmcData->inStock(true);
//                    $gmcData->price($variant->price, 'USD');
//
//                    $shipping = new ProductShipping();
//                    $shipping->price($shippingPrice, 'USD');
//                    $shipping->country('us');
//                    $gmcData->shipping($shipping);
//
//                    $gmcData->offerId($map->product_id);
//                    $gmcData->taxes(['country'=> 'us', 'rate' => 6]);
//                    $gmcData->gtin($variant->barcode);
//                    $gmcData->condition('new');
//                    $gmcData->brand($shop->name);
//                    $gmcData->itemGroupId($rawProduct->system_product_id);
//
//
//                    //print_r($gmcData);
//                    ProductApi::merchant([
//                        'app_name' => $shop->name,
//                        'merchant_id' => $shop->gmc_id,
//                        'client_credentials_path' => storage_path($shop->gmc_credential)
//                    ])->insert($gmcData)->then(function($response){
//                        echo 'Product inserted';
//                    })->otherwise(function($response){
//                        echo 'Insert failed';
//                    })->catch(function($e){
//                        echo($e->getResponse()->getBody()->getContents());
//                    });
//
//                }
//
//                $map->synced = true;
//                $map->save();
//            }
//        });
//        return Command::SUCCESS;
    }
}
