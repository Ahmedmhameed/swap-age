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
        Schema::create('trade_cancellations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trade_id')
                ->unique()
                ->constrained('trades')
                ->cascadeOnDelete();
            $table->foreignId('cancelled_by_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->string('reason', 50);
            $table->text('note')->nullable();
            $table->string('previous_status', 20);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trade_cancellations');
    }
};
