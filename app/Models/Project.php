<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

//use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $guarded = ['_id'];

    public function getCategoriesAttribute($value)
    {
		if (empty($value)) {
			return array();
		}
		
        $get = Category::whereIn('_id', $value)->get()->toArray();
        return $get ?? $value;
    }
}
