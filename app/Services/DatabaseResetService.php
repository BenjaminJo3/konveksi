<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseResetService
{
    private const TABLES = [
        'goods_receipts',
        'delivery_orders',
        'delivery_order_groups',
        'workflow_entries',
        'production_orders',
        'production_order_groups',
        'stock_movements',
        'master_products',
        'master_unit_conversions',
        'master_models',
        'master_sub_categories',
        'master_categories',
        'master_sizes',
        'master_colors',
        'master_units',
        'knowledge_base_articles',
        'role_permissions',
        'audit_logs',
    ];

    public function counts(?int $preservedUserId = null): array
    {
        $counts = [];
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table)) {
                $counts[$table] = DB::table($table)->count();
            }
        }

        if (Schema::hasTable('users')) {
            $counts['users'] = DB::table('users')
                ->when($preservedUserId !== null, fn ($query) => $query->where('id', '!=', $preservedUserId))
                ->count();
        }
        if (Schema::hasTable('branches')) {
            $counts['branches'] = DB::table('branches')->count();
        }
        if (Schema::hasTable('password_reset_tokens')) {
            $counts['password_reset_tokens'] = DB::table('password_reset_tokens')->count();
        }

        return $counts;
    }

    public function reset(User $preservedUser, string $ipAddress, ?string $userAgent): array
    {
        return DB::transaction(function () use ($preservedUser, $ipAddress, $userAgent): array {
            $counts = $this->counts($preservedUser->id);

            if (Schema::hasTable('users')) {
                DB::table('users')->where('id', $preservedUser->id)->update(['branch_id' => null]);
            }

            foreach (self::TABLES as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->delete();
                }
            }

            if (Schema::hasTable('password_reset_tokens')) {
                DB::table('password_reset_tokens')->delete();
            }
            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->where('user_id', '!=', $preservedUser->id)
                    ->orWhereNull('user_id')
                    ->delete();
            }
            if (Schema::hasTable('users')) {
                DB::table('users')->where('id', '!=', $preservedUser->id)->delete();
            }
            if (Schema::hasTable('branches')) {
                DB::table('branches')->delete();
            }

            AuditLog::query()->create([
                'user_id' => $preservedUser->id,
                'event' => 'database_truncated',
                'subject_type' => 'database',
                'subject_id' => (string) DB::connection()->getDatabaseName(),
                'description' => 'Super Admin menghapus seluruh data aplikasi melalui menu Setting.',
                'metadata' => [
                    'deleted_counts' => $counts,
                    'preserved_super_admin_id' => $preservedUser->id,
                    'preserved_tables' => ['migrations', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs'],
                ],
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);

            return $counts;
        });
    }
}
