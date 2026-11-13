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
        Schema::create('evidence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trade_id')
                ->constrained('trades')
                ->cascadeOnDelete();
            $table->foreignId('trade_item_id')
                ->nullable()
                ->constrained('trade_items')
                ->cascadeOnDelete();
            $table->string('kind', 30);
            $table->string('file_path');
            $table->string('mime_type', 100);
            $table->unsignedInteger('size');
            $table->foreignId('uploaded_by_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evidence');
    }
};
