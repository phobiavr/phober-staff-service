<?php

namespace App\Providers;

use App\Events\SessionCreated;
use App\Listeners\BroadcastSessionChanged;
use App\Models\Session;
use App\Models\SnackSale;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
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

        Event::listen(SessionCreated::class, BroadcastSessionChanged::class);
    }
}
