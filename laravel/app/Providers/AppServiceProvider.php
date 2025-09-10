<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Contracts\ReservationRepositoryInterface;
use App\Repositories\Contracts\PassengerRepositoryInterface;
use App\Repositories\Eloquent\ReservationRepository;
use App\Repositories\Eloquent\PassengerRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ReservationRepositoryInterface::class, ReservationRepository::class);
        $this->app->bind(PassengerRepositoryInterface::class, PassengerRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
