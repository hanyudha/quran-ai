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
        Schema::table('embeddings', function (Blueprint $table) {
            // pgvector secara default menyimpan vector dalam format array of floats
            $table->jsonb('embedding')->nullable(); // bisa pakai vector type kalau extension aktif
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('embeddings', function (Blueprint $table) {
            //
            $table->dropColumn('embedding');
        });
    }
};
