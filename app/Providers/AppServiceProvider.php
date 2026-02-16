<?php

namespace App\Providers;

use App\Domain\Admin\AdminRepositoryInterface;
use App\Domain\Auth\JwtTokenServiceInterface;
use App\Infrastructure\Auth\Rs256JwtTokenService;
use App\Infrastructure\Persistence\EloquentAdminRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AdminRepositoryInterface::class, EloquentAdminRepository::class);
        $this->app->bind(JwtTokenServiceInterface::class, Rs256JwtTokenService::class);
    }

    public function boot(): void
    {
        //
    }
}
