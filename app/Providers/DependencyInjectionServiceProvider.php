<?php

namespace App\Providers;

use App\Services\SessionCancelHandler;
use Illuminate\Support\ServiceProvider;
use Phobiavr\PhoberLaravelCommon\Contracts\SessionCancelHandlerInterface;

class DependencyInjectionServiceProvider extends ServiceProvider {
    public function register(): void {
        $this->app->bind(SessionCancelHandlerInterface::class, SessionCancelHandler::class);
    }
}
