<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_order_groups', function (Blueprint $table): void {
            $table->string('spk_signature_path')->nullable();
            $table->foreignId('spk_signed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('spk_signed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('production_order_groups', function (Blueprint $table): void {
            $table->dropForeign(['spk_signed_by']);
            $table->dropColumn(['spk_signature_path', 'spk_signed_by', 'spk_signed_at']);
        });
    }
};
