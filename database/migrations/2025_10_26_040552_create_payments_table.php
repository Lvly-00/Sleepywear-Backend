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
       Schema::create('payments', function (Blueprint $table) {
        $table->id();
        $table->foreignId('order_id')->constrained()->cascadeOnDelete();
        $table->enum('payment_status', ['Unpaid', 'Paid'])->default('Unpaid');
        $table->enum('payment_method', ['Cash', 'GCash', 'Paypal', 'Bank'])->nullable();
        $table->string('payment_image')->nullable();
        $table->integer('total_paid')->default(0);
        $table->timestamp('payment_date')->nullable();
        $table->timestamps();
    });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
