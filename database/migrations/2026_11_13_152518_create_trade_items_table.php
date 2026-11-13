<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('trade_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trade_id')
                ->constrained('trades')
                ->cascadeOnDelete();
            $table->foreignId('product_id')
                ->constrained('products')
                ->restrictOnDelete();
            $table->string('direction', 20);
            $table->unsignedInteger('quantity')->default(1);
            $table->string('condition', 50);
            $table->text('description');
            $table->timestamps();

            $table->index(['product_id', 'direction'], 'trade_items_product_direction_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trade_items');
    }
};
