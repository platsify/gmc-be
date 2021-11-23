<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use MOIREI\GoogleMerchantApi\Facades\ProductApi;

class PushSingleVariationToGMC implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $shop;
    private $gmcData;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($shop, $gmcData)
    {
        $this->shop = $shop;
        $this->gmcData = $gmcData;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        ProductApi::merchant([
            'app_name' => $this->shop->name,
            'merchant_id' => $this->shop->gmc_id,
            'client_credentials_path' => storage_path('app/'.$this->shop->gmc_credential)
        ])->insert($this->gmcData)->then(function($response){
            echo 'Product inserted';
        })->otherwise(function($response){
            echo 'Insert failed';
        })->catch(function($e){
            echo($e->getResponse()->getBody()->getContents());
        });
        echo "\n";
        return;
    }
}
