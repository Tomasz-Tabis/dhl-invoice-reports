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
        if (Schema::hasTable('reports')) {
            Schema::table('reports', function (Blueprint $table) {
                if (! Schema::hasColumn('reports', 'invoice_upload_id')) {
                    $table->foreignId('invoice_upload_id')
                        ->nullable()
                        ->after('user_id')
                        ->constrained()
                        ->cascadeOnDelete();
                }

                if (! Schema::hasColumn('reports', 'selected_drivers')) {
                    $table->json('selected_drivers')->nullable()->after('year');
                }
            });

            return;
        }

        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_upload_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('original_pdf_path');
            $table->string('original_pdf_filename');
            $table->string('generated_pdf_path');
            $table->unsignedTinyInteger('week_number');
            $table->unsignedSmallInteger('year');
            $table->json('selected_drivers')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'year', 'week_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
