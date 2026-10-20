<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Older MySQL / MariaDB (pre-5.7.7 / < 10.2, or innodb_large_prefix off)
        // cap a utf8mb4 index at 191 chars; without this, migrate:fresh fails on
        // string primary keys like password_reset_tokens.email ("1071 key too long").
        // Harmless on newer servers. Keep below any index that needs full length.
        Schema::defaultStringLength(191);
    }
}
