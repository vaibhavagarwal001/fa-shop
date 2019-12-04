<?php

namespace App\Models\Bakingo;

use Illuminate\Database\Eloquent\Model;

class FieldDataFieldProducts extends Model
{
    //
    protected $connection = 'bakingo_mysql';

    protected $table = 'field_data_field_product';

    protected $primaryKey = 'entity_id';
}
