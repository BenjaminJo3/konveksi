<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_units', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 24)->unique();
            $table->string('name', 80)->unique();
            $table->string('symbol', 20);
            $table->timestamps();
        });

        Schema::create('master_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 24)->unique();
            $table->string('name', 100)->unique();
            $table->timestamps();
        });

        Schema::create('master_sub_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained('master_categories')->restrictOnDelete();
            $table->string('code', 24)->unique();
            $table->string('name', 100);
            $table->timestamps();
            $table->unique(['category_id', 'name']);
        });

        Schema::create('master_models', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained('master_categories')->restrictOnDelete();
            $table->foreignId('sub_category_id')->constrained('master_sub_categories')->restrictOnDelete();
            $table->string('code', 24)->unique();
            $table->string('name', 120);
            $table->timestamps();
            $table->unique(['sub_category_id', 'name']);
        });

        Schema::create('master_sizes', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 24)->unique();
            $table->string('name', 40)->unique();
            $table->timestamps();
        });

        Schema::create('master_colors', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 24)->unique();
            $table->string('name', 80)->unique();
            $table->string('hex_color', 7)->nullable();
            $table->timestamps();
        });

        Schema::create('master_unit_conversions', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 24)->unique();
            $table->foreignId('from_unit_id')->constrained('master_units')->restrictOnDelete();
            $table->foreignId('to_unit_id')->constrained('master_units')->restrictOnDelete();
            $table->decimal('factor', 16, 6);
            $table->timestamps();
            $table->unique(['from_unit_id', 'to_unit_id']);
        });

        Schema::create('master_products', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 24)->unique();
            $table->string('name', 160);
            $table->foreignId('category_id')->constrained('master_categories')->restrictOnDelete();
            $table->foreignId('sub_category_id')->constrained('master_sub_categories')->restrictOnDelete();
            $table->foreignId('model_id')->constrained('master_models')->restrictOnDelete();
            $table->foreignId('size_id')->constrained('master_sizes')->restrictOnDelete();
            $table->foreignId('color_id')->constrained('master_colors')->restrictOnDelete();
            $table->foreignId('unit_id')->constrained('master_units')->restrictOnDelete();
            $table->string('fabric_name', 120)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['category_id', 'sub_category_id', 'model_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_products');
        Schema::dropIfExists('master_unit_conversions');
        Schema::dropIfExists('master_colors');
        Schema::dropIfExists('master_sizes');
        Schema::dropIfExists('master_models');
        Schema::dropIfExists('master_sub_categories');
        Schema::dropIfExists('master_categories');
        Schema::dropIfExists('master_units');
    }
};
