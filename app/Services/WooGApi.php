<?php

namespace App\Services;


use Exception;
use Illuminate\Support\Facades\Log;

class WooGApi
{
    public $url, $key;

    function __construct($url, $key)
    {
        $this->url = $url;
        $this->key = $key;
    }

    /**
     * @throws Exception
     */
    public function getRequest($args = array())
    {
        $args['secret'] = $this->key;
        $queryString = http_build_query($args);
        $url = $this->url . '?secret='.$this->key.'&'.$queryString;
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        if(!$response)
        {
            Log::error($curl);
            throw new Exception($curl);
        }
        return json_decode($response);
    }
}
