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
        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('release_date');
            $table->integer('qty')->default(0);
            $table->enum('status', ['Active', 'Sold Out'])->default('Active');
            $table->integer('stock_qty')->default(0);
            $table->decimal('total_sales', 12, 2)->default(0);
            $table->decimal('capital', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collections');
    }
};
