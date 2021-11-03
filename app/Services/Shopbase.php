<?php

namespace App\Services;

class Shopbase extends ApiBase
{
    private $url;
    private $headers = [];

    public $epSmartCollections = '/admin/smart_collections.json';
    public $epCustomCollections = '/admin/custom_collections.json';
    public $epProducts = '/admin/products.json';
    public $epCollectionProducts = '/admin/products/collection.json';

    public function __construct($url, $key, $secret)
    {
        $this->url = rtrim($url, '/');
        $this->url = str_replace('https://', 'https://' . $key . ':' . $secret . '@', $this->url);
    }

    public function getSmartCollections()
    {
        $url = $this->url . $this->epSmartCollections;
        return $this->request($url, 'GET', array(), $this->headers);
    }

    public function getCustomCollections()
    {
        $url = $this->url . $this->epCustomCollections;
        return $this->request($url, 'GET', array(), $this->headers);
    }

    public function getProducts($options = array())
    {
        $url = $this->url . $this->epProducts;
        return $this->request($url, 'GET', $options);
    }

    public function getCollectionProductsByPage($page = 1)
    {
        $url = $this->url . $this->epCollectionProducts;
        return $this->request($url, 'GET', ['page' => $page]);
    }

    public function searchProducts($options = array())
    {
        $url = $this->url . $this->epProducts;
        return $this->request($url, 'GET', $options, $this->headers);
    }

    public function mapSbToProduct($sbProduct, $shop): array
    {
        $productData = array();
        $productData['name'] = $sbProduct->title;
        $productData['url'] = str_replace('//', '/', $shop['public_url'] . '/products/' . $sbProduct->handle);
        $productData['image_url'] = !empty($sbProduct->image) ? $sbProduct->image->src : '';
        $productData['original_id'] = $shop['id'] . '__' . $sbProduct->id;
        $productData['shop_id'] = $shop['id'];
        $productData['original_last_update'] = strtotime($sbProduct->updated_at);
        $productData['sync_gmc'] = false;
        $productData['active'] = $sbProduct->published;

        return $productData;
    }
}
