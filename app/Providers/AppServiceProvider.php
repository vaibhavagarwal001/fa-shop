<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
        DB::listen(function($query){
            Log::channel('daily')->info("SQL - ".$query->sql);
            Log::channel('daily')->info("Bindings - ".json_encode($query->bindings));
            Log::channel('daily')->info("Timings - ".$query->time);
        });
    }
}
