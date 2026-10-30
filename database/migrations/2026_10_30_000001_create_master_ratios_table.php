<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_ratios', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sub_category_id')->constrained('master_sub_categories')->restrictOnDelete();
            $table->foreignId('size_id')->constrained('master_sizes')->restrictOnDelete();
            $table->decimal('ratio', 10, 4);
            $table->timestamps();
            $table->unique(['sub_category_id', 'size_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_ratios');
    }
};
