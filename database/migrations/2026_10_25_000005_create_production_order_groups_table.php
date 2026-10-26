<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_order_groups', function (Blueprint $table): void {
            $table->id();
            $table->string('po_number')->unique();
            $table->date('po_date')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        DB::table('production_orders')
            ->select('po_number')
            ->distinct()
            ->orderBy('po_number')
            ->get()
            ->each(function (object $row): void {
                $order = DB::table('production_orders')
                    ->where('po_number', $row->po_number)
                    ->orderBy('id')
                    ->first(['po_date', 'created_by', 'created_at', 'updated_at']);

                DB::table('production_order_groups')->insert([
                    'po_number' => $row->po_number,
                    'po_date' => $order->po_date ?? null,
                    'created_by' => $order->created_by,
                    'created_at' => $order->created_at,
                    'updated_at' => $order->updated_at,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_order_groups');
    }
};
