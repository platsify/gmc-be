<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
//use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\Model;

class ProductMapCategory extends Model
{
    use HasFactory;
    protected $guarded = ['_id'];

    public function category() {
        return $this->belongsTo(Category::class);
    }
}
