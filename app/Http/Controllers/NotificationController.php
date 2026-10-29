<?php

namespace App\Http\Controllers;

use App\Models\WorkflowEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NotificationController extends Controller
{
    public function markRead(Request $request): JsonResponse
    {
        if (! Schema::hasTable('user_notification_reads')) {
            return response()->json([
                'message' => 'Fitur notifikasi belum tersedia. Jalankan php artisan migrate --force pada server.',
            ], 503);
        }

        $lastReadEntryId = (int) WorkflowEntry::query()->max('id');
        $now = now();

        DB::table('user_notification_reads')->upsert([
            [
                'user_id' => $request->user()->id,
                'last_read_entry_id' => $lastReadEntryId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['user_id'], ['last_read_entry_id', 'updated_at']);

        $unreadCount = WorkflowEntry::query()
            ->where('created_at', '>=', now()->subDay())
            ->where('id', '>', $lastReadEntryId)
            ->count();

        return response()->json(['unread_count' => $unreadCount]);
    }
}
