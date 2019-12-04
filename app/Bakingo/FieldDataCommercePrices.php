<?php

namespace App\Bakingo;

use Illuminate\Database\Eloquent\Model;

class FieldDataCommercePrices extends Model
{
    //
    
    protected $connection = 'bakingo_mysql';

    protected $table = 'field_data_commerce_price';

    protected $primaryKey = 'entity_id';
}
