<?php

namespace App\Models\Bakingo;

use Illuminate\Database\Eloquent\Model;

class CommerceProducts extends Model
{
    //
    protected $connection = 'bakingo_mysql';

    protected $table = 'commerce_product';

    protected $primaryKey = 'product_id';
}
