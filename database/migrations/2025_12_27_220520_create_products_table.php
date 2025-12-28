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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->string('sku', 50)->unique();
            $table->string('name', 255);
            $table->string('subcategory', 100)->nullable();
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2);
            $table->decimal('tax_rate', 5, 2)->default(16.00);
            $table->string('unit', 50)->default('Liter');
            $table->string('packaging', 100)->nullable();
            $table->integer('min_order_quantity')->default(1);
            $table->integer('reorder_level')->default(20);
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('sku');
            $table->index('category_id');
            $table->index('price');
            $table->index('name');
            $table->index('created_at');

            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
