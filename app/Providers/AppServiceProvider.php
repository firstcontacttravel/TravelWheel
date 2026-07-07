<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Large pages (e.g. return-flight search results with 100+ itineraries)
        // can exceed PHP's default PCRE backtrack limit, which makes
        // Livewire's dev-mode root-element check crash with:
        // "DOMDocument::loadHTML(): Argument #1 ($source) must not be empty".
        ini_set('pcre.backtrack_limit', '10000000');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
