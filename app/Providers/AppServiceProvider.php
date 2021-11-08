<?php

namespace App\Providers;

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
        $this->app->singleton(
            \App\Repositories\ProductMapCategory\ProductMapCategoryRepositoryInterface::class,
            \App\Repositories\ProductMapCategory\ProductMapCategoryRepository::class,
        );

        $this->app->singleton(
            \App\Repositories\Category\CategoryRepositoryInterface::class,
            \App\Repositories\Category\CategoryRepository::class
        );

        $this->app->singleton(
            \App\Repositories\Product\ProductRepositoryInterface::class,
            \App\Repositories\Product\ProductRepository::class
        );

        $this->app->singleton(
            \App\Repositories\RawProduct\RawProductRepositoryInterface::class,
            \App\Repositories\RawProduct\RawProductRepository::class
        );

        $this->app->singleton(
            \App\Repositories\Shop\ShopRepositoryInterface::class,
            \App\Repositories\Shop\ShopRepository::class
        );

        $this->app->singleton(
            \App\Repositories\Project\ProjectRepositoryInterface::class,
            \App\Repositories\Project\ProjectRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
