<?php

namespace App\Services;

use Automattic\WooCommerce\Client;

class WooClient
{
    private $wooClient;

    public function __construct($url, $key, $secret, $options = array())
    {
        $this->wooClient = new Client($url, $key, $secret, $options);
    }

    public function getProducts($page = 1, $per_page = 1) {
        return $this->wooClient->get('products', ['page' => $page, 'per_page' => $per_page]);
    }
}
