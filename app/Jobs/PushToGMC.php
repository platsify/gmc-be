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

                $count = 1;
                $category = '2271';
                $multipack = 1;
                $material = 1;
                $isBundle = true;
                $pattern = 'flower';
                $sizeType = 'regular';
                $sizeSystem = 'US';
                $identifierExists = false;
                $shipFromCountry = 'US';

                if (!empty($defaultValues['adults'])) {
                    $adult = (boolean)$defaultValues['adults'];
                }
                if (!empty($defaultValues['gender'])) {
                    $gender = $defaultValues['gender'];
                }
                if (!empty($defaultValues['shipping_price'])) {
                    $shippingPrice = $defaultValues['shipping_price'];
                }
                if (!empty($defaultValues['ageGroup'])) {
                    $ageGroup = $defaultValues['ageGroup'];
                }
                if (!empty($defaultValues['count'])) {
                    $count = $defaultValues['count'];
                }
                if (!empty($defaultValues['category'])) {
                    $category = $defaultValues['category'];
                }
                if (!empty($defaultValues['multipack'])) {
                    $multipack = $defaultValues['multipack'];
                }
                if (!empty($defaultValues['material'])) {
                    $material = $defaultValues['material'];
                }
                if (!empty($defaultValues['isBundle'])) {
                    $isBundle = $defaultValues['isBundle'];
                }
                if (!empty($defaultValues['pattern'])) {
                    $pattern = $defaultValues['pattern'];
                }
                if (!empty($defaultValues['sizeType'])) {
                    $sizeType = $defaultValues['sizeType'];
                }
                if (!empty($defaultValues['sizeSystem'])) {
                    $sizeSystem = $defaultValues['sizeSystem'];
                }
                if (!empty($defaultValues['identifierExists'])) {
                    $identifierExists = $defaultValues['identifierExists'];
                }
                if (!empty($defaultValues['shipFromCountry'])) {
                    $shipFromCountry = $defaultValues['shipFromCountry'];
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

                    // Thay thế default value
                    if (!empty($variant->{'option'.$sizeOption})) {
                        $size = $variant->{'option'.$sizeOption};
                    }
                    if (!empty($variant->{'option'.$colorOption})) {
                        $color = $variant->{'option'.$colorOption};
                    }

                    // Loại bỏ các sản phẩm có Size nhưng ko phải S và Throw
                    if ($rawProduct->options[$sizeOption] == 'Size' && !in_array($size, ['S','Throw', 'Tween', 'Twin'])) {
                        continue;
                    }

                    //  Điều kiện lọc
                    if ($project->require_gtin && empty($variant->barcode)) {
                        echo 'Bỏ qua vì yêu cầu gtin mà variation này ko có';
                        continue;
                    }

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


                    // Map vào GMC
                    $gmcData = new Product();
                    $gmcData->ageGroup($ageGroup);
                    $gmcData->color($color);
                    $gmcData->sizes($size);
                    $gmcData->sizeType($sizeType);
                    $gmcData->sizeSystem($sizeSystem);
                    $gmcData->multipack($multipack);
                    $gmcData->category($category);
                    $gmcData->material($material);
                    $gmcData->pattern($pattern);
                    $gmcData->isBundle($isBundle);
                    $gmcData->identifierExists($identifierExists);
                    $gmcData->gender($gender);
                    $gmcData->adult($adult);
                    $gmcData->title($rawProduct->title . ' - ' . $variant->title);
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
                    $shipping->country($shipFromCountry);
                    $gmcData->shipping($shipping);
                    $gmcData->offerId($variant->id);
                    $gmcData->taxes(['country'=> 'us', 'rate' => 6, 'taxShip' => true]);
                    if (!empty($variant->barcode)) {
                        $gmcData->gtin($variant->barcode);
                    }
                    $gmcData->condition('new');
                    $gmcData->brand($shop->name);
                    $gmcData->itemGroupId($variant->id);


                    PushSingleVariationToGMC::dispatch($shop, $gmcData);
                }

                $map->synced = true;
                $map->save();
            }
        });
        return Command::SUCCESS;
    }
}
