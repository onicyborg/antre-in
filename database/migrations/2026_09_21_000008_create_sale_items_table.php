<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sale_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->restrictOnDelete();
            $table->string('product_name', 150);
            $table->string('sku', 50);
            $table->string('unit_name', 30);
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('unit_price');
            $table->unsignedBigInteger('cost_price')->default(0);
            $table->unsignedBigInteger('subtotal');
            $table->timestamps();
            $table->unique(['sale_id', 'product_id']);
        });
    }

    public function down(): void { Schema::dropIfExists('sale_items'); }
};
