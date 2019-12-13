<?php

namespace App\Http\Controllers\Component;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


class ValidateComponent extends Controller
{
    //

    public function valid($input) {
        // check for invalid chars
        $arr = ["`","~","!","@","#","$","%","^","&","*","(",")","+","=","-","}","{","[","]","?","<",">","/","\\","'",'"',",","."];
        $input = trim(str_replace($arr, "" , $input));
        return $input;
    }

}
