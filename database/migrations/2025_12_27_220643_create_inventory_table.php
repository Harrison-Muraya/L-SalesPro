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
        Schema::create('inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained()->onDelete('cascade');
            $table->integer('quantity')->default(0)->comment('Total quantity');
            $table->integer('reserved_quantity')->default(0)->comment('Reserved for orders');
            $table->integer('available_quantity')->default(0)->comment('Available for sale');
            $table->timestamp('last_restock_date')->nullable();
            $table->timestamps();

            // Unique constraint - one inventory record per product per warehouse
            $table->unique(['product_id', 'warehouse_id'], 'product_warehouse_unique');
            
            // Indexes
            $table->index('product_id');
            $table->index('warehouse_id');
            $table->index('quantity');
            $table->index('available_quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory');
    }
};
