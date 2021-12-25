<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
//use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\Model;

class VariantWhitelist extends Model
{
    use HasFactory;
    protected $guarded = ['_id'];

}
