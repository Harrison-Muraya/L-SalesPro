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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->enum('type', ['Garage', 'Dealership', 'Distributor', 'Retailer']);
            $table->enum('category', ['A', 'A+', 'B', 'C'])->default('C');
            $table->string('contact_person', 255);
            $table->string('phone', 20);
            $table->string('email')->unique();
            $table->string('tax_id', 50)->unique();
            $table->integer('payment_terms')->default(30)->comment('Payment terms in days');
            $table->decimal('credit_limit', 12, 2)->default(0);
            $table->decimal('current_balance', 12, 2)->default(0);
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->text('address');
            $table->string('territory', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();

             // Indexes
            $table->index('email');
            $table->index('tax_id');
            $table->index('category');
            $table->index('type');
            $table->index('name');
            $table->index(['latitude', 'longitude']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
