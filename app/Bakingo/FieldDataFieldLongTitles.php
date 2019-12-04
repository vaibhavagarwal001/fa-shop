<?php

namespace App\Bakingo;

use Illuminate\Database\Eloquent\Model;

class FieldDataFieldLongTitles extends Model
{
    //
    protected $connection = 'bakingo_mysql';

    protected $table = 'field_data_field_long_title';

    protected $primaryKey = 'entity_id';
}
