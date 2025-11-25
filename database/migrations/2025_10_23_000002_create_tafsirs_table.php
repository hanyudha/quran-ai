<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tafsirs', function (Blueprint $table) {
            $table->id();

            // Relasi ke ayat dan sumber tafsir
            $table->foreignId('ayah_id')->constrained('ayahs')->onDelete('cascade');
            $table->foreignId('source_id')->constrained('tafsir_sources')->onDelete('cascade');

            // Teks tafsir
            $table->text('short_text')->nullable(); // ringkasan (bisa diambil dari JSON)
            $table->longText('long_text')->nullable(); // tafsir lengkap

            // Metadata tambahan
            $table->string('language', 10)->default('id');
            $table->timestamps();

            // Untuk mencegah duplikasi tafsir (satu ayat - satu sumber)
            $table->unique(['ayah_id', 'source_id']);
            $table->index(['ayah_id', 'source_id']);
            $table->index('source_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tafsirs');
    }
};
