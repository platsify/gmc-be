<?php

namespace App\Jobs;

use App\Models\Gmc;
use App\Models\ProductMapProjects;
use App\Models\Project;
use App\Models\RawProduct;
use App\Models\Shop;
use Illuminate\Bus\Queueable;
use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use MOIREI\GoogleMerchantApi\Contents\Product\Product;
use MOIREI\GoogleMerchantApi\Contents\Product\ProductShipping;
use MOIREI\GoogleMerchantApi\Facades\ProductApi;

class PushToGMC implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	public $timeout = 0;
	
    private $projectId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($projectId)
    {
        $this->projectId = $projectId;
    }

    /**
     * Execute the job.
     *
     * @return int
     */
    public function handle()
    {
        $project = Project::find($this->projectId);
        if (!$project) {
            return Command::SUCCESS;
        }

        $shop = Shop::find($project->shop_id);
        if (!$shop) {
            return Command::SUCCESS;
        }

        // Fill default value for product
        $defaultValues = array();
        foreach ($project->default_values as $default_value) {
            $keyName = $default_value['name'];
            $keyValue = $default_value['value'];
            if (empty($rawProduct->{$keyName})) {
                $defaultValues[$keyName] = $keyValue;
            }
        }

        ProductMapProjects::where('project_id', $project->id)->where('synced', false)->chunk(10, function ($maps) use ($project, $shop, $defaultValues) {
            foreach ($maps as $map) {
                $rawProduct = RawProduct::where('system_product_id', $map->product_id)->first();
                if (!$rawProduct) {
                    return Command::SUCCESS;
                }

                if (!$rawProduct->variants) {
                    print_r($rawProduct->id);
                    continue;
                }

                // Các field có thể ghi đè
                //Adult, Gender, shipping_price, ageGroup, color
                $shippingPrice = 5.05;
                $adult = false;
                $gender = 'unisex';
                $ageGroup = 'adult';
                $color = 'multicolor';
                $size = 'Free size';
                if (isset($defaultValues['adults'])) {
                    $adult = (boolean)$defaultValues['adults'];
                }
                if (isset($defaultValues['gender'])) {
                    $gender = $defaultValues['gender'];
                }
                if (isset($defaultValues['shipping_price'])) {
                    $shippingPrice = $defaultValues['shipping_price'];
                }
                if (isset($defaultValues['ageGroup'])) {
                    $ageGroup = $defaultValues['ageGroup'];
                }

                // Tìm số thự tự của color và size trong options
                $colorOption = 2;
                $sizeOption = 1;
                foreach ($rawProduct->options as $item) {
                    $item = (object) $item;
                    if ($item->name == 'Size') {
                        $sizeOption = $item->position;
                    }
                    if ($item->name == 'Color') {
                        $colorOption = $item->position;
                    }
                }

                foreach ($rawProduct->variants as $variant) {
                    $variant = (object) $variant;

                    //  Điều kiện lọc
                    if ($project->require_gtin && empty($variant->barcode)) {
                        continue;
                    }

                    if (!empty($project->only_option1) && (!isset($variant->option1) || $variant->option1 != $project->only_option1)) {
                        //echo 'Option 1 not match';
                        continue;
                    }
                    if (!empty($project->only_option2) && (!isset($variant->option2) || $variant->option2 != $project->only_option2)) {
                        //echo 'Option 2 not match';
                        continue;
                    }
                    if (!empty($project->only_option3) && (!isset($variant->option3) || $variant->option3 != $project->only_option3)) {
                        continue;
                    }

                    // Thay thế default value
                    if (!empty($variant->{'option'.$sizeOption})) {
                        $size = $variant->{'option'.$sizeOption};
                    }
                    if (!empty($variant->{'option'.$colorOption})) {
                        $color = $variant->{'option'.$colorOption};
                    }

                    // Map vào GMC
                    $gmcData = new Product();
                    $gmcData->ageGroup($ageGroup);
                    $gmcData->color($color);
                    $gmcData->sizes($size);
                    $gmcData->gender($gender);
                    $gmcData->adult($adult);
                    $gmcData->title($variant->title);
                    $gmcData->description($rawProduct->body_html);
                    //$gmcData->id($gmcData->channel . ':'.$gmcData->contentLanguage.':'.$gmcData->targetCountry.':'.$gmcData->offerId);
                    $gmcData->link(rtrim( $shop->public_url, '/').'/'.$rawProduct->handle);
                    $gmcData->image($rawProduct->image['src']);
                    $gmcData->lang('en');
                    $gmcData->country('us');
                    $gmcData->online('online');
                    $gmcData->inStock(true);
                    $gmcData->price($variant->price, 'USD');

                    $shipping = new ProductShipping();
                    $shipping->price($shippingPrice, 'USD');
                    $shipping->country('us');
                    $gmcData->shipping($shipping);

                    $gmcData->offerId($variant->id);
                    $gmcData->taxes(['country'=> 'us', 'rate' => 6]);
                    $gmcData->gtin($variant->barcode);
                    $gmcData->condition('new');
                    $gmcData->brand($shop->name);
                    //$gmcData->itemGroupId($rawProduct->system_product_id);


                    PushSingleVariationToGMC::dispatch($shop, $gmcData);
                }

                $map->synced = true;
                $map->save();
            }
        });
        return Command::SUCCESS;
    }
}
