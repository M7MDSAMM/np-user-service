<?php

namespace App\Providers;

use App\Domain\Admin\AdminRepositoryInterface;
use App\Domain\Auth\JwtTokenServiceInterface;
use App\Infrastructure\Auth\Rs256JwtTokenService;
use App\Infrastructure\Persistence\EloquentAdminRepository;
use App\Services\Contracts\UserDeviceServiceInterface;
use App\Services\Contracts\UserPreferenceServiceInterface;
use App\Services\Contracts\UserServiceInterface;
use App\Services\Implementations\UserDeviceService;
use App\Services\Implementations\UserPreferenceService;
use App\Services\Implementations\UserService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AdminRepositoryInterface::class, EloquentAdminRepository::class);
        $this->app->bind(JwtTokenServiceInterface::class, Rs256JwtTokenService::class);

        $this->app->bind(UserServiceInterface::class, UserService::class);
        $this->app->bind(UserPreferenceServiceInterface::class, UserPreferenceService::class);
        $this->app->bind(UserDeviceServiceInterface::class, UserDeviceService::class);
    }

    public function boot(): void
    {
        //
    }
}
