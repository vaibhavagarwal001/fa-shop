<?php

namespace App\Models\Bakingo;

use Illuminate\Database\Eloquent\Model;

class Api_product_price extends BKModel
{
    //
    protected $table = 'api_product_price';

    protected $primaryKey = 'sid';

    protected $fillable = [
        'merchant_id',
        'nid',
        'pid',
        'price',
        'sprice',
        'cprice',
        'weight',
        'sku',
    ];
}
