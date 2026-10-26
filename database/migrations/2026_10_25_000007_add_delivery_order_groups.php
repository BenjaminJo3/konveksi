<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_order_groups', function (Blueprint $table): void {
            $table->id();
            $table->string('number')->unique();
            $table->string('po_number');
            $table->string('source_role');
            $table->string('destination_role');
            $table->string('goods_type');
            $table->string('unit', 30);
            $table->date('dispatched_at');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['destination_role', 'dispatched_at']);
        });

        Schema::table('delivery_orders', function (Blueprint $table): void {
            $table->foreignId('delivery_order_group_id')
                ->nullable()
                ->after('id')
                ->constrained('delivery_order_groups')
                ->nullOnDelete();
        });

        DB::table('delivery_orders')->orderBy('id')->each(function (object $deliveryOrder): void {
            $groupId = DB::table('delivery_order_groups')->insertGetId([
                'number' => $deliveryOrder->number,
                'po_number' => DB::table('production_orders')->where('id', $deliveryOrder->production_order_id)->value('po_number'),
                'source_role' => $deliveryOrder->source_role,
                'destination_role' => $deliveryOrder->destination_role,
                'goods_type' => $deliveryOrder->goods_type,
                'unit' => $deliveryOrder->unit,
                'dispatched_at' => $deliveryOrder->dispatched_at,
                'notes' => $deliveryOrder->notes,
                'created_by' => $deliveryOrder->created_by,
                'created_at' => $deliveryOrder->created_at,
                'updated_at' => $deliveryOrder->updated_at,
            ]);

            DB::table('delivery_orders')
                ->where('id', $deliveryOrder->id)
                ->update(['delivery_order_group_id' => $groupId]);
        });
    }

    public function down(): void
    {
        Schema::table('delivery_orders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('delivery_order_group_id');
        });

        Schema::dropIfExists('delivery_order_groups');
    }
};
