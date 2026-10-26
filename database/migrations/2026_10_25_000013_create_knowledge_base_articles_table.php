<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_base_articles', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 80)->unique();
            $table->string('category', 100);
            $table->string('title', 160);
            $table->text('summary');
            $table->json('steps');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_base_articles');
    }
};
