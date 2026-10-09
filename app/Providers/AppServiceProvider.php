<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
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
        // N+1を防ぐため、本番以外では一覧で取得したモデルの遅延読み込みを例外にする
        Model::preventLazyLoading(! $this->app->isProduction());
    }
}
