<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('number_sequences', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type', 10);
            $table->date('date');
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();
            $table->unique(['type', 'date']);
        });
    }

    public function down(): void { Schema::dropIfExists('number_sequences'); }
};
