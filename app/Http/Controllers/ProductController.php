<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ShoppingContent\ProductsShoppingContent;
use Illuminate\Http\Request;
use MOIREI\GoogleMerchantApi\Facades\ProductApi;
use MOIREI\GoogleMerchantApi\Contents\Product\Product as GMProduct;

class ProductController extends Controller
{
    public function index()
    {
        $product = Product::find(1);
        $product = (new GMProduct)->with($product);

        ProductApi::merchant('merchant_2')->insert($product)->then(function($data){
            echo 'Product inserted';
            print_r($data);
        })->otherwise(function(){
            echo 'Insert failed';
        })->catch(function($e){
            dump($e);
        });
        return response()->json(['hello' => 'haha'], 201);
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $product = new Product();
        $product->name = 'Product '.time();
        $product->image_url = 'https://www.congtybaovethanglong.com/uploads/files/2019/02/13/dong-phuc-bao-ve-thong-tu-08-ao-xanh-duong-quan-tim-than.jpg';
        $product->price = rand(10, 40);
        $product->currency = 'USD';
        $product->condition = 'new';
        $product->save();

        return response()->json(['success' => true, 'data' => $product]);
    }

    public function show(Product $product)
    {
        //
    }

    public function edit(Product $product)
    {
        //
    }

    public function update(Request $request, Product $product)
    {
        //
    }

    public function destroy(Product $product)
    {
        //
    }
}
