<?php

namespace App\Services;

class Shopbase extends ApiBase
{
    private $url;
    private $headers;

    public $epSmartCollections = '/admin/smart_collections.json';
    public $epCustomCollections = '/admin/custom_collections.json';
    public $epProducts = '/admin/products.json';

    public function __construct($url, $key, $secret)
    {
        $this->url = $url;
        $this->headers = ['Authorization: Basic '.base64_encode($key.':'.$secret)];
    }

    public function getSmartCollections() {
        $url = $this->url . $this->epSmartCollections;
        return $this->request($url, 'GET', array(), $this->headers);
    }

    public function getCustomCollections() {
        $url = $this->url . $this->epSmartCollections;
        return $this->request($url, 'GET', array(), $this->headers);
    }

    public function getProductsByPage($page = 1) {
        $url = $this->url . $this->epProducts;
        return $this->request($url, 'GET', ['page' => $page]);
    }

    public function searchProducts($options = array()) {
        $url = $this->url . $this->epProducts;
        return $this->request($url, 'GET', $options, $this->headers);
    }
}
