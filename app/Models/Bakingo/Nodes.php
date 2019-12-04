<?php

namespace App\Models\Bakingo;

use Illuminate\Database\Eloquent\Model;

class Nodes extends Model
{
    //
    protected $connection = 'bakingo_mysql';

    protected $table = 'node';

    protected $primaryKey = 'nid';
}
