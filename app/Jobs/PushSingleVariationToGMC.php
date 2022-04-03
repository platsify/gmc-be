<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use MOIREI\GoogleMerchantApi\Facades\ProductApi;
use mysql_xdevapi\Exception;

class PushSingleVariationToGMC implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $shop;
    private $gmcData;
	private $map;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($shop, $gmcData, $map)
    {
        $this->shop = $shop;
        $this->gmcData = $gmcData;
		$this->map = $map;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
		//echo storage_path('app/'.$this->shop->gmc_credential)."\n\n";
        ProductApi::merchant([
            'app_name' => $this->shop->name,
            'merchant_id' => $this->shop->gmc_id,
            'client_credentials_path' => storage_path('app/'.$this->shop->gmc_credential)
        ])->insert($this->gmcData)->then(function($response){
			$this->map->synced = true;
			$this->map->save();
           echo "Done \n";
        })->otherwise(function($response){
           echo "otherwise \n";
            throw new Exception($response);
        })->catch(function($e){
            echo "Catch ".$e->getMessage() ."\n";
            throw new Exception($e);
        });

        //echo "Hoho \n";
        return;
    }
}
