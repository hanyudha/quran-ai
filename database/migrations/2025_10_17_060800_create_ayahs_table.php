<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ayahs', function (Blueprint $table) {
            $table->id();

            // Identitas dasar
            $table->integer('surah')->index();                // Surah number (1..114)
            $table->integer('ayah_in_quran')->unique();       // 1..6236
            $table->integer('ayah_in_surah');                 // Nomor ayat dalam surah

            // Isi ayat
            $table->text('text_ar');                          // Teks Arab
            $table->text('text_id');                          // Terjemahan Indonesia
            $table->text('tafsir')->nullable();               // Tafsir singkat/panjang

            // Informasi tambahan dari JSON
            $table->jsonb('audio')->nullable();               // {"alafasy": "https://cdn..."}
            $table->jsonb('image')->nullable();               // {"source": "..."}
            $table->jsonb('sajda')->nullable();               // {"recommended": true, "obligatory": false}

            // Metadata Al-Qur'an
            $table->integer('juz')->nullable();
            $table->integer('page')->nullable();
            $table->integer('manzil')->nullable();
            $table->integer('ruku')->nullable();
            $table->integer('hizb_quarter')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ayahs');
    }
};
