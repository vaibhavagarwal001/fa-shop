<?php

namespace App\Models\Bakingo;

use Illuminate\Database\Eloquent\Model;

class Api_attribute_map extends BKModel
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
