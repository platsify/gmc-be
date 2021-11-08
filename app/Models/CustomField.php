<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
//use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\Model;

class CustomField extends Model
{
    use HasFactory;

    protected $guarded = ['_id'];

    public const CUSTOM_FIELD_OBJECT_TYPE_PRODUCT = 1;
    public const CUSTOM_FIELD_OBJECT_TYPE_PROJECT = 2;
}
