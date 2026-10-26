<?php

namespace App\Providers;

use App\Models\WorkflowEntry;
use Illuminate\Support\Facades\View;
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
        View::composer('layouts.app', function ($view): void {
            $view->with('recentNotifications', WorkflowEntry::query()
                ->with(['order:id,po_number', 'user:id,name'])
                ->latest()
                ->limit(6)
                ->get());
            $view->with('notificationCount', WorkflowEntry::query()
                ->where('created_at', '>=', now()->subDay())
                ->count());
        });
    }
}
