<?php
namespace App\Providers;

use App\Services\WalletService;
use Illuminate\Support\ServiceProvider;

class WalletServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WalletService::class, function ($app) {
            return new WalletService();
        });
    }
}
