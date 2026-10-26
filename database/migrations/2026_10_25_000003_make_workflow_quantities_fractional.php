<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workflow_entries', function (Blueprint $table): void {
            $table->decimal('quantity', 12, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('workflow_entries', function (Blueprint $table): void {
            $table->unsignedInteger('quantity')->nullable()->change();
        });
    }
};
