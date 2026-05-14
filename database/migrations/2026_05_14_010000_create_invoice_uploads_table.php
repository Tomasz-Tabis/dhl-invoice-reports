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
        Schema::create('invoice_uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('original_pdf_path');
            $table->string('original_pdf_filename');
            $table->json('parsed_data');
            $table->unsignedTinyInteger('week_number');
            $table->unsignedSmallInteger('year');
            $table->timestamps();

            $table->index(['user_id', 'year', 'week_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_uploads');
    }
};
