<?php

namespace App\Providers;

use App\Support\Admin\ActivityRecorder;
use App\Workflow\SyncsWorkItems;
use App\Workflow\WorkAccess;
use App\Workflow\WorkflowRegistry;
use Filament\Actions\Action;
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

        $this->app->singleton(WorkflowRegistry::class);
        $this->app->scoped(WorkAccess::CACHE, fn () => new \ArrayObject);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ActivityRecorder::register();
        SyncsWorkItems::register();

        // Any action on a booking the signed-in person may not take is hidden,
        // and Filament refuses a hidden action even if called directly.
        Action::configureUsing(fn (Action $action) => $action->hidden(fn (): bool => WorkAccess::blocks($action)));
    }
}
