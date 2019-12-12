<?php

namespace App\Models\Bakingo;

use Illuminate\Database\Eloquent\Model;

class Api_products extends BKModel
{
    //
    protected $table = 'api_products';

    protected $primaryKey = 'sid';

    public $timestamps = false;
    const CREATED_AT = 'created';
    const UPDATED_AT = 'updated';

    protected $fillable = [
        'merchant_id',
        'nid',
        'type',
        'title',
        'status',
        'long_title',
        'alias',
        'city',
        'amount',
        'sell_price',
        'cost_price',
        'product_category',
        'mini_descr',
        'descr',
        'created',
        'updated'
     ];
}
