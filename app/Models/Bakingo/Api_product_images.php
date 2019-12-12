<?php

namespace App\Models\Bakingo;

use Illuminate\Database\Eloquent\Model;

class Api_product_images extends BKModel
{
    //
    protected $table = 'api_product_images';

    protected $primaryKey = 'sid';
    
    // protected $hidden = ['created_at', 'updated_at'];
    public $timestamps = false;

    protected $fillable = [
        'merchant_id',
        'nid',
        'fid',
        'filename',
        'uri',
        'field_images_alt',
        'field_images_title',
        'sort_order'
    ];
}
