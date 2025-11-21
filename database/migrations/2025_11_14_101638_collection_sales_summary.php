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
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('collection_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('collection_name');
            $table->decimal('collection_capital', 15, 2)->default(0);
            $table->date('date');
            $table->decimal('total_sales', 15, 2)->default(0);
            $table->integer('total_items_sold')->default(0);
            $table->integer('total_customers')->default(0);
            $table->timestamps();

            $table->unique(['collection_id', 'date']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('collection_sales_summary');
    }
};
