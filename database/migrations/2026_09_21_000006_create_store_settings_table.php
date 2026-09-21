<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('store_settings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('store_name', 100)->default('antre-in');
            $table->text('address')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('receipt_footer', 255)->default('Terima kasih atas kunjungan Anda');
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->unsignedInteger('draft_expire_hours')->default(24);
            $table->unsignedInteger('max_active_drafts')->default(20);
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('store_settings'); }
};
