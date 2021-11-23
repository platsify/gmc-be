<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
//use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\Model;

class RawProduct extends Model
{
    use HasFactory;
    protected $guarded = ['_id'];

    public function productMapCategories() {
        return $this->hasMany(ProductMapCategory::class, 'product_id', 'system_product_id');
    }
}
