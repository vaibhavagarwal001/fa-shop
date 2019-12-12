<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Api_attribute_map extends Model
{
    //
    protected $table = 'api_attribute_map';

    public $timestamps = false;

    protected $fillable = [
        'merchant_id',
        'nid',
        'attr_id',
        'attr_type',
    ];
}
