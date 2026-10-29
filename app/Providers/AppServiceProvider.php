<?php

namespace App\Providers;

use App\Models\WorkflowEntry;
use Illuminate\Support\Facades\DB;
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
            $lastReadNotificationId = (int) (DB::table('user_notification_reads')
                ->where('user_id', auth()->id())
                ->value('last_read_entry_id') ?? 0);

            $view->with('recentNotifications', WorkflowEntry::query()
                ->with(['order:id,po_number', 'user:id,name'])
                ->latest()
                ->limit(6)
                ->get());
            $view->with('notificationCount', WorkflowEntry::query()
                ->where('created_at', '>=', now()->subDay())
                ->where('id', '>', $lastReadNotificationId)
                ->count());
        });
    }
}
