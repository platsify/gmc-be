<?php

namespace App\Jobs;

use App\Models\Gmc;
use App\Models\ProductMapProjects;
use App\Models\Project;
use App\Models\RawProduct;
use App\Models\Shop;
use App\Models\VariantBlacklist;
use App\Models\VariantWhitelist;
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

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Execute the job.
     *
     * @return int
     */
    public function handle()
    {
        // Comment tạm 2512
        //$activeProjects = ['619f3f968e5de9606219e65c', '619f3ea5ff798b78771ed965'];
        $activeProjects = Project::where('active', true)->get()->pluck('_id')->toArray();
        if (!$activeProjects || count($activeProjects) == 0) {
            return;
        }

        shuffle($activeProjects);
        $projectId = $activeProjects[0];
        echo 'Push project '.$projectId."\n";
        $maps = ProductMapProjects::where('project_id', $projectId)->where('synced', false)->limit(3000)->get();

        if (!$maps) {
            echo 'Het map roi' . "\n";
            return;
        }
        //echo count($maps);
        foreach ($maps as $map) {
            $project = Project::where('_id', $map->project_id)->first();
            if (!$project) {
                echo 'Project ko con ton tai, da xoa' . "\n";
                $map->delete();
                continue;
            }

            $shop = Shop::find($project->shop_id);
            if (!$shop) {
                echo 'Shop ko con ton tai, da xoa' . "\n";
                $map->delete();
                continue;
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


            $rawProduct = RawProduct::where('system_product_id', $map->product_id)->with('productMapCategories', 'productMapCategories.category')->first();
            if (!$rawProduct) {
                echo 'Ko thay raww' . "\n";
                return Command::SUCCESS;
            }

			if ($rawProduct->isWooProduct) {
				$cloneRawProduct = clone $rawProduct;
				if (empty($rawProduct->variants)) {
					$myVariants = array();
					$myVariants[0] = $cloneRawProduct;
					$rawProduct->variants = $myVariants;
				}
			}
            if (!$rawProduct->variants) {
                echo "Ko co variants \n";
                continue;
            }

            if ($rawProduct->isWooProduct) {
                if (empty($rawProduct->images) || !$rawProduct->images[0]['src']) {
                    echo $rawProduct->id. " Khong co anh\n";
                    continue;
                }
            } else {
                if (!$rawProduct->image['src']) {
                    echo $rawProduct->id. " Khong co anh\n";
                    continue;
                }
            }



            // Các field có thể ghi đè
            //Adult, Gender, shipping_price, ageGroup, color
            $shippingPrice = 5.05;
            $adult = false;
            $gender = 'unisex';
            $ageGroup = 'adult';
            $color = 'multicolor';
            $size = 'Free size';
            $type = '';
            $count = 1;
            $category = '';
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

            // Nếu ko có category thì ko đẩy
            if (empty($category)) {
                echo "Khong co category \n" .$rawProduct->id;
                continue;
            }
            // Tìm số thự tự của color và size trong options
            $colorOption = 99999;
            $sizeOption = 99999;
            $typeOption = 9999;


            $options = $rawProduct->options;
            if ($rawProduct->isWooProduct) {
                $options = $rawProduct->attributes;
            }
            foreach ($options as $item) {
                $item = (object)$item;
                if ($item->name == 'Size') {
                    $sizeOption = $item->position;
                }
                if ($item->name == 'Color') {
                    $colorOption = $item->position;
                }
                if ($item->name == 'Type') {
                    $typeOption = $item->position;
                }
            }

            foreach ($rawProduct->variants as $variant) {
                $variant = (object)$variant;
                $inBlackList = VariantBlacklist::where('variant_id', $variant->id)->first();
                if ($inBlackList) {
                    echo $variant->id. " nam trong blacklist\n";
                    continue;
                }
                if ($rawProduct->isWooProduct) {
                    if (empty($variant->sku)) {
                        echo "SKU rong ".$rawProduct->system_product_id."\n";
                        continue;
                    }
                    $inBlackList = VariantBlacklist::where('variant_id', $variant->sku)->first();
                    if ($inBlackList) {
                        echo $variant->sku . " co SKU nam trong blacklist\n";
                        continue;
                    }
                }

                // Thay thế default value
                if ($rawProduct->isWooProduct) {
                    $buildTitle = $rawProduct->name;
                    foreach ($variant->attributes as $attribute) {
                        $attribute = (object)$attribute;
                        if ($attribute->name == 'Size') {
                            $size = $attribute->option;
                            $buildTitle .= ' Size: '.$size;
                        }
                        if ($attribute->name == 'Color') {
                            $color = $attribute->option;
                            $buildTitle .= ' Color: '.$color;
                        }
                        if ($attribute->name == 'Type') {
                            $type = $attribute->option;
                            $buildTitle .= ' Type: '.$type;
                        }
                    }
                } else {
                    $buildTitle = $rawProduct->title . ' - ' . $variant->title;
                    if (!empty($variant->{'option' . $sizeOption})) {
                        $size = $variant->{'option' . $sizeOption};
                    }
                    if (!empty($variant->{'option' . $colorOption})) {
                        $color = $variant->{'option' . $colorOption};
                    }
                    if (!empty($variant->{'option' . $typeOption})) {
                        $type = $variant->{'option' . $typeOption};
                    }
                }

                // Tìm xem SP này có thuộc collection là bedding ko
                $isBeddingCollection = false;
                foreach ($rawProduct->productMapCategories as $productCategory) {
                    if ($productCategory->category && strpos(mb_strtolower($productCategory->category->name), 'bedding') !== false) {
                        $isBeddingCollection = true;
                        break;
                    }
                }
                if ($isBeddingCollection) {
                    if ($type != 'Quilt Cover + 2 Pillow Cases') {
                        continue;
                    }
                }

                // Tìm xem SP này có thuộc collection là hoodie ko
                $isHoodieCollection = false;
                foreach ($rawProduct->productMapCategories as $productCategory) {
                    if ($productCategory->category && strpos(mb_strtolower($productCategory->category->name), 'hoodie') !== false) {
                        $isHoodieCollection = true;
                        break;
                    }
                }
                if ($isHoodieCollection) {
                    if ($type != 'AOP Hoodie') {
                        continue;
                    }
                }

                // Loại bỏ các sản phẩm có Size nhưng ko phải 'S', 'Throw', 'Tween', 'Twin'
                //echo $rawProduct->options[$sizeOption-1]['name'] . ' = ' .$size."\n";
                if ($sizeOption != 99999 && !in_array($size, ['S', 'Throw', 'Tween', 'Twin'])) {
                    //echo 'Bỏ qua'. "\n";
                    continue;
                }

                //  Điều kiện lọc
                if ($project->require_gtin && empty($variant->barcode)) {
                    // echo 'Bỏ qua vì yêu cầu gtin mà variation này ko có';
                    continue;
                }

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
                if ($rawProduct->isWooProduct) {
					$gmcData->offerId($variant->sku);
					$gmcData->itemGroupId($variant->sku);
					$imageLink = $variant->image['src'];
					if (!$imageLink) {
						$imageLink = $variant->images[0]['src'];
						if (!$imageLink) {
							continue;
						}
					}
                    $gmcData->description($rawProduct->description);
                    $gmcData->link($variant->permalink);
                    $gmcData->image($imageLink);
                    // Tìm Gtin, mpn
                    foreach ($rawProduct->meta_data as $meta_datum) {
                        if ($meta_datum['key'] == '_wpsso_product_gtin' && !empty($meta_datum['value'])) {
                            $gmcData->gtin($meta_datum['value']);
                        }
                        if ($meta_datum['key'] == '_wpsso_product_mfr_part_no' && !empty($meta_datum['value'])) {
                            $gmcData->mpn($meta_datum['value']);
                        }
                    }

                    foreach ($variant->meta_data as $meta_datum) {
                        if ($meta_datum['key'] == '_wpsso_product_gtin' && !empty($meta_datum['value'])) {
                            $gmcData->gtin($meta_datum['value']);
                        }
                        if ($meta_datum['key'] == '_wpsso_product_mfr_part_no' && !empty($meta_datum['value'])) {
                            $gmcData->mpn($meta_datum['value']);
                        }
                    }
                } else {
					$gmcData->offerId($variant->id);
					$gmcData->itemGroupId($variant->id);
                    $gmcData->description($rawProduct->body_html);
                    $gmcData->link(rtrim($shop->public_url, '/') . '/products/' . $rawProduct->handle . '?variant=' . $variant->id);
                    $gmcData->image($rawProduct->image['src']); // TODO: Tìm ảnh cho từng Variant
                    if (!empty($variant->barcode)) {
                        $gmcData->gtin($variant->barcode);
                    }
                }

                //$gmcData->id($gmcData->channel . ':'.$gmcData->contentLanguage.':'.$gmcData->targetCountry.':'.$gmcData->offerId);
                $gmcData->title(mb_substr($buildTitle, 0, 150));
                $gmcData->link = str_replace('/products/products/', '/products/', $gmcData->link);

                // Build them cac bien con lai
                $gmcData->lang('en');
                $gmcData->online('online');
                $gmcData->inStock(true);
                $gmcData->price($variant->price, 'USD');
                $gmcData->country($shipFromCountry);
                $shipping = new ProductShipping();
                $shipping->price($shippingPrice, 'USD');
                $shipping->country($shipFromCountry);
                $gmcData->shipping($shipping);
                $gmcData->taxes(['country' => 'us', 'rate' => 6, 'taxShip' => true]);
                $gmcData->condition('new');
                $gmcData->brand($shop->name);

                //$gmcData->customValues(['ships_from_country' => $shipFromCountry]);

                $countCustomLabel = 0;
                foreach ($rawProduct->productMapCategories as $productCategory) {
                    if ($productCategory->category) {
                        if ($countCustomLabel == 0) {
                            $gmcData->customLabel0($productCategory->category->name);
                        }
//                            if ($countCustomLabel == 1) {
//                                $gmcData->customLabel1($productCategory->category->name);
//                            }
//                            if ($countCustomLabel == 2) {
//                                $gmcData->customLabel2($productCategory->category->name);
//                            }
//                            if ($countCustomLabel == 3) {
//                                $gmcData->customLabel3($productCategory->category->name);
//                            }
//                            if ($countCustomLabel == 4) {
//                                $gmcData->customLabel4($productCategory->category->name);
//                            }
                        $countCustomLabel++;
                        if ($countCustomLabel == 4) {
                            break;
                        }
                    }
                }

                if (!$project->todaySync) {
                    $project->todaySync = str_pad(date("d", time()), 2, '0', STR_PAD_LEFT) . '_' . str_pad(0, 5, '0', STR_PAD_LEFT);
                } else {
                    $parts = explode('_', $project->todaySync);
                    $date = $parts[0];
                    if ($date == str_pad(date("d", time()), 2, '0', STR_PAD_LEFT)) {
                        $cou = $parts[1];
                        $maxCou = 15000;
                        if ($project->maxPerDay) {
                            $maxCou = $project->maxPerDay;
                        }
                        if ($cou > $maxCou) {
                            //echo 'Da het han muc ngay ' . $cou . "\n";
                            return;
                        } else {
                            $cou++;
                        }
                    } else {
                        $date = str_pad(date("d", time()), 2, '0', STR_PAD_LEFT);
                        $cou = 1;
                    }
                    $project->todaySync = str_pad($date, 2, '0', STR_PAD_LEFT) . '_' . str_pad($cou, 5, '0', STR_PAD_LEFT);
                }
                $project->save();
                PushSingleVariationToGMC::dispatch($shop, $gmcData, $map)->onQueue('gmc');
                echo 'Add job PushSingleVariationToGMC cho id'.$variant->id."\n";
                //echo $cou . "\n";
            }
        }
        return Command::SUCCESS;
    }
}
