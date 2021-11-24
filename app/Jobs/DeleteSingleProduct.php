<?php

namespace App\Jobs;

use App\Models\ProductMapCategory;
use App\Models\ProductMapProjects;
use App\Models\Shop;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use MOIREI\GoogleMerchantApi\Contents\Product\Product;
use MOIREI\GoogleMerchantApi\Facades\ProductApi;

class DeleteSingleProduct implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $shop;
    private $product_id;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($shop, $product_id)
    {
        $this->shop = $shop;
        $this->product_id = $product_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $gmcData = new Product();
        $gmcData->channel('online');
        $gmcData->lang('en');
        $gmcData->country('US');
        $gmcData->offerId($this->product_id);

        echo 'Delete '.$this->product_id."\n";
        //echo  storage_path('app/'.$this->shop->gmc_credential);
        ProductApi::merchant([
            'app_name' => $this->shop->name,
            'merchant_id' => $this->shop->gmc_id,
            'client_credentials_path' => storage_path('app/'.$this->shop->gmc_credential)
        ])->delete($gmcData)->then(function($response){
            echo 'Deleted';
        })->otherwise(function(){
            //echo 'Delete failed';
        })->catch(function($e){
            //dump($e);
        });

        return;
    }
}