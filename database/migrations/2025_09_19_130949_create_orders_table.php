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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->text('address');
            $table->string('contact_number');
            $table->string('social_handle');
            $table->timestamp('order_date')->useCurrent();
            $table->decimal('total', 12, 2)->default(0);
            $table->enum('payment_status', ['unpaid', 'paid'])->default('unpaid');
            $table->enum('payment_method', ['Cash', 'GCash', 'Paypal', 'Bank'])->nullable();
            $table->string('payment_image')->nullable();
            $table->decimal('total_paid', 12, 2)->default(0);
            $table->timestamp('payment_date')->nullable()->after('payment_status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
