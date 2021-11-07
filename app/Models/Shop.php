<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
//use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\Model;

class Shop extends Model
{
    use HasFactory;

    public const SHOP_TYPE_SHOPBASE = 1;
    public const SHOP_TYPE_WOO = 2;

    public const SHOP_SYNC_RUNNING = 1;
    public const SHOP_SYNC_DONE = 9;

    protected $guarded = ['_id'];

}
