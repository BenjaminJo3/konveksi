<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_orders', function (Blueprint $table): void {
            $table->dropUnique('production_orders_po_number_unique');
            $table->index('po_number');
            $table->date('po_date')->nullable();
            $table->string('category')->nullable();
            $table->string('sub_category')->nullable();
            $table->string('model')->nullable();
            $table->string('size')->nullable();
            $table->decimal('yard_kg', 12, 2)->nullable();
            $table->decimal('rib_qty', 12, 2)->nullable();
            $table->decimal('ratio', 12, 4)->nullable();
            $table->decimal('ratio_result_qty', 13, 4)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('production_orders', function (Blueprint $table): void {
            $table->dropIndex(['po_number']);
            $table->unique('po_number');
            $table->dropColumn([
                'po_date',
                'category',
                'sub_category',
                'model',
                'size',
                'yard_kg',
                'rib_qty',
                'ratio',
                'ratio_result_qty',
            ]);
        });

    }
};
