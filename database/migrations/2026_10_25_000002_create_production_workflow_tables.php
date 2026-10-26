<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_orders', function (Blueprint $table): void {
            $table->id();
            $table->string('po_number')->unique();
            $table->string('product_name');
            $table->string('color')->nullable();
            $table->string('fabric_name')->nullable();
            $table->unsignedInteger('order_quantity');
            $table->decimal('fabric_estimate', 10, 2)->default(0);
            $table->decimal('fabric_ratio', 10, 3)->default(0);
            $table->decimal('fabric_delivered', 10, 2)->nullable();
            $table->unsignedInteger('spk_quantity')->nullable();
            $table->string('status')->default('draft');
            $table->date('delivery_due_at')->nullable();
            $table->text('close_note')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('workflow_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('production_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('reference')->nullable();
            $table->decimal('quantity', 12, 2)->nullable();
            $table->unsignedInteger('secondary_quantity')->nullable();
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['production_order_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_entries');
        Schema::dropIfExists('production_orders');
    }
};
