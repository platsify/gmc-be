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
use Illuminate\Support\Facades\Log;

class PushSingleVariationToGMC implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	public $tries = 2;

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
           echo "Done ". $this->gmcData->title."\n";
        })->otherwise(function($response){
            Log::error($this->gmcData->offerId. ': '.$this->gmcData->title.': '.$response);
            throw new Exception($response);
        })->catch(function($e){
            Log::error($this->gmcData->offerId. ': '.$this->gmcData->title.': '. $e->getMessage());
            throw new Exception($e);
        });

        //echo "Hoho \n";
        return;
    }
}
