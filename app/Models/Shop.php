<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    use HasFactory;

    public const SHOP_TYPE_SHOPBASE = 1;
    public const SHOP_TYPE_WOO = 2;
}
