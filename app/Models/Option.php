<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\Model;

//use Illuminate\Database\Eloquent\Model;

class Option extends Model
{
    use HasFactory;

    protected $guarded = ['_id'];

}
