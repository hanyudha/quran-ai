<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tafsir_sources', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique(); // contoh: kemenag, quraish, jalalayn
            $table->string('name', 150);          // nama lengkap sumber tafsir
            $table->string('author', 150)->nullable(); // penulis / penyusun tafsir
            $table->string('language', 10)->default('id'); // bahasa utama tafsir
            $table->text('description')->nullable(); // deskripsi tentang sumber tafsir
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tafsir_sources');
    }
};
