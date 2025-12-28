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
        Schema::create('stock_reservation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('reservation_reference', 100)->unique();
            $table->integer('quantity');
            $table->enum('status', ['pending', 'confirmed', 'released', 'expired'])->default('pending');
            $table->timestamp('expires_at');
            $table->timestamps();

            // Indexes
            $table->index('reservation_reference');
            $table->index('status');
            $table->index('expires_at');
            $table->index('order_id');
            $table->index('product_id');
            $table->index('warehouse_id');
            $table->index(['status', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_reservation');
    }
};
