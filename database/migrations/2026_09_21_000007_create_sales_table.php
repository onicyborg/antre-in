<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('status', 20)->index();
            $table->string('draft_number', 30)->nullable()->unique();
            $table->string('invoice_number', 30)->nullable()->unique();
            $table->string('label', 100)->nullable();
            $table->foreignUuid('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('locked_at')->nullable();
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->string('discount_type', 10)->nullable();
            $table->unsignedBigInteger('discount_value')->default(0);
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->unsignedBigInteger('tax_amount')->default(0);
            $table->unsignedBigInteger('total')->default(0);
            $table->string('payment_method', 20)->nullable();
            $table->unsignedBigInteger('paid_amount')->nullable();
            $table->unsignedBigInteger('change_amount')->nullable();
            $table->string('payment_reference', 100)->nullable();
            $table->string('note', 255)->nullable();
            $table->timestamp('drafted_at')->nullable();
            $table->timestamp('completed_at')->nullable()->index();
            $table->timestamp('voided_at')->nullable();
            $table->foreignUuid('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('void_reason', 255)->nullable();
            $table->timestamp('discarded_at')->nullable();
            $table->string('discard_reason', 20)->nullable();
            $table->timestamps();
            $table->index(['status', 'completed_at']);
        });
    }

    public function down(): void { Schema::dropIfExists('sales'); }
};
