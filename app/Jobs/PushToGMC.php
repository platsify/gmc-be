<?php

namespace App\Jobs;

use App\Models\Gmc;
use App\Models\ProductMapProjects;
use App\Models\Project;
use App\Models\RawProduct;
use App\Models\Shop;
use App\Models\Option;
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
use Illuminate\Support\Facades\Log;

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
        $maps = [];

        // ưu tiên project do admin chỉ định
        $optionProject = Option::where('name', 'high_priority_project')->first();
        if ($optionProject) {
            $projectId = $optionProject->value;
            if (!empty($projectId)) {
                $maps = ProductMapProjects::where('project_id', $projectId)->where('synced', false)->where('push_variant_count', '!=', (int)0)->limit(3000)->get();
                //$maps = ProductMapProjects::where('product_id', '6250747df1266e052713df74')->limit(3000)->get();
                if ($maps && count($maps) > 0) {
                    echo 'Đang push project ưu tiên project ' . $projectId . "\n";
                } else {
                    echo "Tìm thấy project ưu tiên nhưng ko có map phù hợp \n";
                }
            }
        }


        // Nhưng nếu project đấy hết map rồi thì sẽ random
        if (!$maps) {
            $activeProjects = Project::where('active', true)->get()->pluck('_id')->toArray();
            if (!$activeProjects || count($activeProjects) == 0) {
                echo 'Het projects';
                return;
            }
            shuffle($activeProjects);
            $projectId = $activeProjects[0];
            echo 'Push project ' . $projectId . "\n";
            $maps = ProductMapProjects::where('project_id', $projectId)->where('synced', false)->where('push_variant_count', '!=', (int)0)->limit(3000)->get();
            //$projectId = '6249417b51d1847f98434378';
            //$maps = ProductMapProjects::where('project_id', '6249417b51d1847f98434378')->get();
            echo 'Push project ' . $projectId . "\n";
            if (!$maps || count($maps) == 0) {
                echo 'Het map roi' . "\n";
            }
        }

        echo 'Maps co tong so:' . count($maps) . "\n";
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
                    echo $rawProduct->id . " Khong co anh\n";
                    continue;
                }
            } else {
                if (!$rawProduct->image['src']) {
                    echo $rawProduct->id . " Khong co anh\n";
                    continue;
                }
            }


            // Các field có thể ghi đè
            //Adult, Gender, shipping_price, ageGroup, color
            $shippingPrice = 5.05;
            $adult = false;
            $gender = 'unisex';
            $ageGroup = 'adult';
            $color = '';
            $size = '';
            $brand = $shop->name;
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
            if (!empty($defaultValues['color'])) {
                $color = $defaultValues['color'];
            }
            if (!empty($defaultValues['shipping_price'])) {
                $shippingPrice = $defaultValues['shipping_price'];
            }
            if (!empty($defaultValues['shippingPrice'])) {
                $shippingPrice = $defaultValues['shippingPrice'];
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
                echo "Khong co category \n" . $rawProduct->id;
                continue;
            }
            // Tìm số thự tự của color và size trong options
            $colorOption = 99999;
            $sizeOption = 99999;
            $typeOption = 99999;


            $options = $rawProduct->options;
            if ($rawProduct->isWooProduct) {
                if (empty($rawProduct->attributes)) {
                    $rawProduct->attributes = array();
                }
                $options = $rawProduct->attributes;
            }
            foreach ($options as $item) {
                $item = (object)$item;
                if (mb_strtolower($item->name) == 'size') {
                    $sizeOption = $item->position;
                }
                if (mb_strtolower($item->name) == 'color') {
                    $colorOption = $item->position;
                }
                if (mb_strtolower($item->name) == 'type') {
                    $typeOption = $item->position;
                }
            }


            // Nếu category là shoes thì chỉ lấy variant đầu tiên
            foreach ($rawProduct->productMapCategories as $productCategory) {
                if ($productCategory->category && strpos(mb_strtolower($productCategory->category->name), 'shoes') !== false) {
                    if ($rawProduct->variants && count($rawProduct->variants) > 0) {
                        $rawProduct->variants = [$rawProduct->variants[0]];
                    }
                }
            }

            $map->push_variant_count = 0;
            foreach ($rawProduct->variants as $d => $variant) {
                echo $rawProduct->system_product_id . ' variant so ' . $d . "\n";
                try {
                    // Map vào GMC
                    $gmcData = new Product();
                    $variant = (object)$variant;
                    if (!is_array($variant->attributes)) {
                        $variant->attributes = array();
                    }

                    //$inBlackList = VariantBlacklist::where('variant_id', $variant->id)->where('gmc_id', $shop->gmc_id)->first();
                    $inBlackList = VariantBlacklist::where('variant_id', $variant->id)->first();
                    if ($inBlackList) {
                        echo $variant->id . " nam trong blacklist\n";
                        continue;
                    }
                    if ($rawProduct->isWooProduct) {
                        if (empty($variant->sku)) {
                            echo "SKU rong " . $rawProduct->system_product_id . "\n";
                            continue;
                        }
                        $inBlackList = VariantBlacklist::where('variant_id', $variant->sku)->where('gmc_id', $shop->gmc_id)->first();
                        if ($inBlackList) {
                            echo $variant->sku . " co SKU nam trong blacklist\n";
                            continue;
                        }
                    }

                    // Thay thế default value
                    if ($rawProduct->isWooProduct) {
						// echo 'XOA '. __LINE__;
                        $buildTitle = $rawProduct->name;


                        foreach ($rawProduct->attributes as $attribute) {
                            $attribute = (object)$attribute;
                            if (mb_strtolower($attribute->name) == 'size' && count($attribute->options) > 0) {
                                $size = $this->myUcFirst($attribute->options[0]);
                            }
                            if (mb_strtolower($attribute->name) == 'gender' && count($attribute->options) > 0) {
                                $gender = $this->myUcFirst($attribute->options[0]);
                            }
                            if (mb_strtolower($attribute->name) == 'color' && count($attribute->options) > 0) {
                                $color = $this->myUcFirst($attribute->options[0]);
                            }
                            if (mb_strtolower($attribute->name) == 'type' && count($attribute->options) > 0) {
                                $type = $this->myUcFirst($attribute->options[0]);
                            }
                            if (mb_strtolower($attribute->name) == 'brand' && count($attribute->options) > 0) {
                                $brand = $this->myUcFirst($attribute->options[0]);
                            }
                            if (mb_strtolower($attribute->name) == 'gtin' && count($attribute->options) > 0) {
                                $gmcData->gtin($attribute->options[0]);
                            }
                            if (mb_strtolower($attribute->name) == 'mpn' && count($attribute->options) > 0) {
                                $gmcData->mpn($attribute->options[0]);
                            }
                        }

// echo 'XOA '. __LINE__;
                        foreach ($variant->attributes as $attribute) {
                            $attribute = (object)$attribute;
                            if (mb_strtolower($attribute->name) == 'size') {
                                $size = $this->myUcFirst($attribute->value ?? $attribute->option);
                            }
                            if (mb_strtolower($attribute->name) == 'gender') {
                                $gender = $this->myUcFirst($attribute->value ?? $attribute->option);
                            }
                            if (mb_strtolower($attribute->name) == 'color') {
                                $color = $this->myUcFirst($attribute->value ?? $attribute->option);
                            }
                            if (mb_strtolower($attribute->name) == 'type') {
                                $type = $this->myUcFirst($attribute->value ?? $attribute->option);
                            }
                            if (mb_strtolower($attribute->name) == 'brand') {
                                $brand = $this->myUcFirst($attribute->option ?? $attribute->value);
                            }
                            if (mb_strtolower($attribute->name) == 'gtin') {
                                $gmcData->gtin($attribute->value ?? $attribute->option);
                            }
                            if (mb_strtolower($attribute->name) == 'mpn') {
                                $gmcData->mpn($attribute->value ?? $attribute->option);
                            }
                        }
// echo 'XOA '. __LINE__;
                        $extendTitles = [];
                        if (!empty($type)) {
                            $extendTitles[] = $type;
                        }
                        if (!empty($color)) {
                            $extendTitles[] = $color;
                        }
                        if (!empty($size)) {
                            $extendTitles[] = $size;
                        }
                        if (!empty($extendTitles)) {
                            $buildTitle .= ' - ' . implode(' / ', $extendTitles);
                        }

                        // với các site trên WooCommerce
                        //hiện tại đang lấy sku để gửi lên trường id và item-group-id trên gmc
                        //chỉnh sửa:
                        //1- lấy trường id của sản phẩm để gửi lên id và item_group_id trên gmc
                        //2- riêng site kniben thì để như hiện tại, lấy trường sku để gửi lên trường id và item_group_id
                        if ($rawProduct->shop_id == '628f226c4c30d21b5c5203d5') {
                            $gmcData->offerId($variant->sku);
                            $gmcData->itemGroupId($variant->sku);
                        } else {
                            $iOfferId = $variant->id;
                            $idParts = explode('__', $variant->id);
                            if (!empty($idParts)) {
                                $iOfferId = $idParts[count($idParts) - 1];
                            }
                            $gmcData->offerId($iOfferId);
                            $gmcData->itemGroupId($iOfferId);
                        }
// echo 'XOA '. __LINE__;
                        $imageLink = null;
                        if (!$imageLink) {
                            if (!empty($variant->images)) {
                                $imageLink = !empty($variant->images) ? $variant->images[0]['src'] : '';
                            }
                            if (!$imageLink) {
                                $imageLink = !empty($rawProduct->images) ? $rawProduct->images[0]['src'] : '';
                            }
                            if (!$imageLink) {
                                echo "Khoong co link anh nen bo qua \n";
                                continue;
                            }
                        }
                        $gmcData->description(mb_substr($rawProduct->description, 0, 5000));
                        $gmcData->link($variant->permalink);
                        $gmcData->image($imageLink);

                        // Tìm Gtin, mpn
                        foreach ($rawProduct as $k => $v) {
                            if ($k == 'woosea_gtin' && !empty($v)) {
                                $gmcData->gtin($v);
                            }
                            if ($k == 'woosea_mpn' && !empty($v)) {
                                $gmcData->mpn($v);
                            }
                        }
// echo 'XOA '. __LINE__;
                        foreach ($variant as $k => $v) {
                            if ($k == 'woosea_gtin' && !empty($v)) {
                                $gmcData->gtin($v);
                            }
                            if ($k == 'woosea_mpn' && !empty($v)) {
                                $gmcData->mpn($v);
                            }
                        }
                    } else {
                        // Shopebase product
                        $gmcData->offerId($variant->id);
                        $gmcData->itemGroupId($variant->id);
                        $gmcData->description(mb_substr($rawProduct->body_html, 0, 5000));
                        $gmcData->link(rtrim($shop->public_url, '/') . '/products/' . $rawProduct->handle . '?variant=' . $variant->id);
                        $gmcData->image($rawProduct->image['src']); // TODO: Tìm ảnh cho từng Variant
                        if (!empty($variant->barcode)) {
                            $gmcData->gtin($variant->barcode);
                        }

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
// echo 'XOA '. __LINE__;
                    // Tìm xem SP này có thuộc collection là quilt ko, nếu có thì chỉ lấy Throw
                    $isQuilt = false;
                    foreach ($rawProduct->productMapCategories as $productCategory) {
                        if ($productCategory->category && strpos(mb_strtolower($productCategory->category->name), 'quilt') !== false) {
                            $isQuilt = true;
                            break;
                        }
                    }
                    if ($isQuilt) {
                        if ($sizeOption != 99999 && mb_strtolower($size) != 'throw') {
                            echo 'Thuoc muc Quilt nhung size = ' . $size . " nen bo qua \n";
                            continue;
                        }
                    }

                    // Tìm xem SP này có thuộc collection là bedding ko,  nếu có thì chỉ lấy Quilt Cover + 2 Pillow Cases' hoặc 'Duvet Cover + 2 Pillow Cases'
                    $isBeddingCollection = false;
                    foreach ($rawProduct->productMapCategories as $productCategory) {
                        if ($productCategory->category && strpos(mb_strtolower($productCategory->category->name), 'bedding') !== false) {
                            $isBeddingCollection = true;
                            break;
                        }
                    }
                    if ($isBeddingCollection) {
                        if ($typeOption != 99999 && mb_strtolower($type) != 'quilt cover + 2 pillow cases' && mb_strtolower($type) != 'duvet cover + 2 pillow cases') {
                            echo 'Thuoc muc bedding nhung $type = ' . $type . " nen bo qua \n";
                            continue;
                        }
                    }
					// echo 'XOA '. __LINE__;
                    // Tìm xem SP này có thuộc collection là hoodie ko, nếu có thì chỉ lấy aop hoodie
                    $isHoodieCollection = false;
                    foreach ($rawProduct->productMapCategories as $productCategory) {
                        if ($productCategory->category && strpos(mb_strtolower($productCategory->category->name), 'hoodie') !== false) {
                            $isHoodieCollection = true;
                            break;
                        }
                    }
					// echo 'XOA '. __LINE__;
                    if ($isHoodieCollection) {
                        if ($typeOption != 99999 && mb_strtolower($type) != 'aop hoodie') {
                            echo 'Thuoc muc hoodie nhung $type = ' . $type . " nen bo qua \n";
                            continue;
                        } else {
                            echo 'Thuoc muc hoodie va $type = ' . $type . " dc chap nhan \n";
                        }
                    }

                    // Loại bỏ các sản phẩm có Size nhưng ko phải 'S', 'Throw', 'Tween', 'Twin', '3'
                    if ($sizeOption != 99999 && !in_array(mb_strtolower($size), ['s', 'throw', 'tween', 'twin', '3'])) {
                        echo 'Size = ' . $size . " khong thuoc danh sach cho phep ['S', 'Throw', 'Tween', 'Twin', '3'] nen bo qaaua \n";
                        continue;
                    }
                    echo 'SSSSSSize = ' . $size . " =====\n";
// echo 'XOA '. __LINE__;

                    //  Điều kiện lọc
                    if ($project->require_gtin && empty($variant->barcode)) {
                        echo 'Bỏ qua vì yêu cầu gtin mà variation này ko có';
                        continue;
                    }

                    $gmcData->ageGroup($ageGroup);
                    $gmcData->color(!empty($color) ? $color : 'multicolor');
                    if (!empty($size)) {
                        $gmcData->sizes($size);
                    }
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
                    if ($project->include_brand) {
                        $gmcData->brand($brand);
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
                    //echo 'Shipping price: '.$shippingPrice."\n";
                    $shipping->country($shipFromCountry);
                    $gmcData->shipping($shipping);
                    $gmcData->taxes(['country' => 'us', 'rate' => 6, 'taxShip' => true]);
                    $gmcData->condition('new');
                    $gmcData->customLabel1($project->name);

                    //$gmcData->customValues(['ships_from_country' => $shipFromCountry]);
                    $countCustomLabel = 0;
                    foreach ($rawProduct->productMapCategories as $productCategory) {
                        if ($productCategory->category) {
                            if ($countCustomLabel == 0) {
                                $gmcData->customLabel0($productCategory->category->name);
                            }
////                            if ($countCustomLabel == 1) {
////                                $gmcData->customLabel1($productCategory->category->name);
////                            }
////                            if ($countCustomLabel == 2) {
////                                $gmcData->customLabel2($productCategory->category->name);
////                            }
////                            if ($countCustomLabel == 3) {
////                                $gmcData->customLabel3($productCategory->category->name);
////                            }
////                            if ($countCustomLabel == 4) {
////                                $gmcData->customLabel4($productCategory->category->name);
////                            }
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
                    echo 'Add job PushSingleVariationToGMC cho id ' . $variant->id . "\n";
                    //exit();
                    $map->push_variant_count++;
                    //echo $cou . "\n";
                } catch (\Exception $e) {
                    Log::error('Product: ' . $map->product_id . ': ' . $e->getMessage() . '. LINE: ' . $e->getLine());
                }
            }
            $map->save();
        }
        return Command::SUCCESS;
    }

    function myUcFirst($str) {
        $str = trim($str);
        if (empty($str)) {
            return $str;
        }
        if ($str == '5xl') {
            return '5XL';
        }
        if ($str == '4xl') {
            return '4XL';
        }
        if ($str == '3xl') {
            return '3XL';
        }
        if ($str == '2xl') {
            return '2XL';
        }
        if ($str == 'xl') {
            return 'XL';
        }

        return ucfirst($str);

    }
}
