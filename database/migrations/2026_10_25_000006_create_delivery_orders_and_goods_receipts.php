<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_orders', function (Blueprint $table): void {
            $table->id();
            $table->string('number')->nullable()->unique();
            $table->foreignId('production_order_id')->constrained()->restrictOnDelete();
            $table->string('source_role');
            $table->string('destination_role');
            $table->string('goods_type');
            $table->decimal('quantity', 12, 2);
            $table->string('unit', 30);
            $table->date('dispatched_at');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['destination_role', 'dispatched_at']);
        });

        Schema::create('goods_receipts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('delivery_order_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity_received', 12, 2);
            $table->text('discrepancy_note')->nullable();
            $table->foreignId('received_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('received_at');
            $table->timestamps();
            $table->index(['delivery_order_id', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipts');
        Schema::dropIfExists('delivery_orders');
    }
};
