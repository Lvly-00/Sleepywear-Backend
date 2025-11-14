<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collection_sales_summary', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('collection_id')->nullable();
            $table->string('collection_name');
            $table->decimal('collection_capital', 15, 2)->default(0); // store capital at time of sale
            $table->date('date'); // day of sales
            $table->decimal('total_sales', 15, 2)->default(0); // revenue
            $table->integer('total_items_sold')->default(0);
            $table->integer('total_customers')->default(0);
            $table->timestamps();

            $table->unique(['collection_id', 'date']); // one record per collection per day
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_sales_summary');
    }
};
