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
        Schema::create('dashboard_metrics', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->decimal('gross_income', 15, 2);
            $table->decimal('net_income', 15, 2);
            $table->integer('total_items_sold');
            $table->integer('total_invoices');
            $table->json('collection_sales');
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dashboard_metrics');
    }
};
