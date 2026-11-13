<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('trades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignId('counterparty_id')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();
            $table->string('status', 20)->default('draft');
            $table->unsignedInteger('version')->default(1);
            $table->decimal('cash_amount_owner', 10, 2)->unsigned()->nullable();
            $table->decimal('cash_amount_counterparty', 10, 2)->unsigned()->nullable();
            $table->string('cash_currency', 3)->nullable();
            $table->timestamp('confirmed_by_owner_at')->nullable();
            $table->timestamp('confirmed_by_counterparty_at')->nullable();
            $table->timestamp('deadline_at')->nullable();
            $table->string('completed_type', 30)->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(
                ['owner_id', 'status', 'created_at', 'id'],
                'trades_owner_status_created_idx'
            );
            $table->index(['counterparty_id', 'status'], 'trades_counterparty_status_idx');
            $table->index(['status', 'deadline_at'], 'trades_status_deadline_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trades');
    }
};
