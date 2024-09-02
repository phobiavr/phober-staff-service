<?php

namespace App\Providers;

use App\Models\Session;
use App\Models\SnackSale;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider {
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {
        Relation::morphMap([
            'staff-session'    => Session::class,
            'staff-snack-sale' => SnackSale::class,
        ]);
    }
}
