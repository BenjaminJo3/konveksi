<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_products', function (Blueprint $table): void {
            $table->foreignId('model_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('master_products', function (Blueprint $table): void {
            $table->foreignId('model_id')->nullable(false)->change();
        });
    }
};
