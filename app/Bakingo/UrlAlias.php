<?php

namespace App\Bakingo;

use Illuminate\Database\Eloquent\Model;

class UrlAlias extends Model
{
    //
    protected $connection = 'bakingo_mysql';

    protected $table = 'url_alias';

    protected $primaryKey = 'pid';
}
