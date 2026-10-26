<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 120)->unique();
            $table->string('address')->nullable();
            $table->string('phone', 40)->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('branch_id')->nullable()->after('role')->constrained('branches')->nullOnDelete();
        });

        Schema::table('production_orders', function (Blueprint $table): void {
            $table->foreignId('branch_id')->nullable()->after('created_by')->constrained('branches')->nullOnDelete();
            $table->index(['branch_id', 'po_date']);
        });
    }

    public function down(): void
    {
        Schema::table('production_orders', function (Blueprint $table): void {
            $table->dropForeign(['branch_id']);
            $table->dropIndex(['branch_id', 'po_date']);
            $table->dropColumn('branch_id');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });

        Schema::dropIfExists('branches');
    }
};
