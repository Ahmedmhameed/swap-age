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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trade_id')
                ->constrained('trades')
                ->cascadeOnDelete();
            $table->foreignId('reviewer_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignId('reviewed_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment_text')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['trade_id', 'reviewer_id'], 'reviews_trade_reviewer_unique');
            $table->index(['reviewed_id', 'created_at'], 'reviews_reviewed_created_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
