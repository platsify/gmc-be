<?php

namespace App\Services;

use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Log;

class ApiBase
{
    public function request($url, $method = 'GET', $data = array(), $headers = array())
    {
        try {
            $defaultHeaders = array(
                'accept: application/json, text/plain, */*',
                'content-type: application/json;charset=UTF-8',
                'sec-fetch-site: cross-site',
                'sec-fetch-mode: cors',
                'sec-fetch-dest: empty',
            );

            $headers = array_merge($defaultHeaders, $headers);

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => $headers,
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            return json_decode($response);
        } catch (\Exception $e) {
            Log::error($e);
            return null;
        }
    }
}
