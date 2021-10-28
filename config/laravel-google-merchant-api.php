<?php

return [

    /*
	|--------------------------------------------------------------------------
	| Default merchant
	|--------------------------------------------------------------------------
	|
	| Set the default merchant from below configurations.
	*/

    // 'default_merchant' => 'moirei',

    /*
	|--------------------------------------------------------------------------
	| API Mode
	|--------------------------------------------------------------------------
	|
	| Set the API version to use.
	*/

    'version' => 'v2.1',

    /*
	|--------------------------------------------------------------------------
	| Client Config
	|--------------------------------------------------------------------------
	|
	| Optional configurations for the guzzle client.
	| Accepts only 'timeout', 'headers', 'proxy', 'allow_redirects', 'http_errors', 'decode_content', 'verify', 'cookies'
	*/

    'client_config' => [
        'timeout' => 7.0, // in seconds, allow sufficient time on each call since instantiating each request has to also authenticate
        'proxy' => null, // null values are ignored
    ],

    /*
	|--------------------------------------------------------------------------
	| Merchant Credentials
	|--------------------------------------------------------------------------
	|
	| Nerchant credentials' configurations
	*/

    'merchants' => [
        'merchant_1' => [
            'app_name' => env('MERCHANT_NAME_1', null),
            'merchant_id' => env('MERCHANT_ID_1', ''),
            'client_credentials_path' => storage_path('app/google-merchant-api/'.env('MERCHANT_NAME_1').'.json'),
        ],
        'merchant_2' => [
            'app_name' => env('MERCHANT_NAME_2', null),
            'merchant_id' => env('MERCHANT_ID_2', ''),
            'client_credentials_path' => storage_path('app/google-merchant-api/'.env('MERCHANT_NAME_2').'.json'),
        ]
    ],

    /*
	|--------------------------------------------------------------------------
	| Contents Config
	|--------------------------------------------------------------------------
	|
	| Configuration for the API contents
	*/

    'contents' => [
        'products' => [
            'model' => \App\Models\Product::class,
            'attributes' => [
                /*
                 * Options:
                 *
                 * 'offerId', 'title', 'description', 'link', 'imageLink',
                 * 'contentLanguage' (defaults to "en"),
                 * 'targetCountry' (defaults to "AU"),
                 * 'channel' (defaults to "online"),
                 * 'condition' (defaults to "new"),
                 * 'availability' (defaults to "in stock"),
                 * 'ageGroup', 'availabilityDate', 'brand', 'color',
                 * 'gender', 'googleProductCategory', 'gtin', 'itemGroupId', 'mpn',
                 * 'price', 'sizes', 'customAttributes',
                 */

                'offerId' => 'id',
                'title' => 'name',
                'description' => 'description',
                'link' => 'url',
                'imageLink' => 'image_url',
                'availability' => 'in_stock', // "in stock", "out of stock", or "preorder"
                'brand' => 'brand',
                'mpn' => 'mpn',
                'price' => 'gm_price', // must return array (2): value, and currency
            ],
            'defaults' => [
                'contentLanguage' => 'en',
                'targetCountry' => 'US',
                'channel' => 'online',
                'availability' => 'in stock',
                'condition' => 'new',
            ],
        ],
        'orders' => [
            'schedule_orders_check' => true,
            'schedule_frequency' => 'daily',
            'debug_scout' => false,
        ],
    ],

    /*
	|--------------------------------------------------------------------------
	| Default Currency
	|--------------------------------------------------------------------------
	|
	| Default product currency
	*/

    'default_currency' => 'USD',
];
