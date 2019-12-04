<?php

namespace App\Bakingo;

use Illuminate\Database\Eloquent\Model;

class FieldDataFieldMiniDescriptions extends Model
{
    //
    protected $connection = 'bakingo_mysql';

    protected $table = 'field_data_field_mini_description';

    protected $primaryKey = 'entity_id';
}
