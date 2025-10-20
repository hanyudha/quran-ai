<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS vector;');

        // 1️⃣ Surahs table
        Schema::create('surahs', function (Blueprint $table) {
            $table->id();
            $table->integer('number')->unique();
            $table->string('name_ar', 100)->nullable();
            $table->string('name_id', 100)->nullable();
            $table->string('translation', 255)->nullable();
            $table->string('revelation', 20)->nullable();
            $table->integer('number_of_ayahs')->nullable();
            $table->text('description')->nullable();
            $table->string('audio', 255)->nullable();
            $table->text('bismillah_ar')->nullable();
            $table->text('bismillah_id')->nullable();
            $table->jsonb('bismillah_audio')->nullable();
            $table->timestamps();
        });

        // 2️⃣ Ayahs table
        Schema::create('ayahs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surah_id')->constrained('surahs')->cascadeOnDelete();
            $table->integer('ayah_in_quran');
            $table->integer('ayah_in_surah');
            $table->text('text_ar');
            $table->text('text_id')->nullable();
            $table->jsonb('audio')->nullable();
            $table->jsonb('image')->nullable();
            $table->jsonb('tafsir')->nullable();
            $table->jsonb('meta')->nullable();
            $table->timestamps();
        });

        // 3️⃣ Embeddings table
        // Schema::create('embeddings', function (Blueprint $table) {
        //     $table->id();
        //     $table->foreignId('ayah_id')->constrained('ayahs')->cascadeOnDelete();
        //     $table->vector('embedding', 1536);
        //     $table->timestamps();
        // });

        // // Index untuk similarity search cepat
        // DB::statement("
        //     CREATE INDEX idx_embeddings_vector_cosine
        //     ON embeddings
        //     USING ivfflat (embedding vector_cosine_ops)
        //     WITH (lists = 100);
        // ");
        // Schema::create('embeddings', function (Blueprint $table) {
        //     $table->id();

        //     // Relasi ke ayahs
        //     $table->foreignId('ayah_id')
        //         ->unique() // 1 ayat = 1 embedding
        //         ->constrained('ayahs')
        //         ->onDelete('cascade');

        //     // Vektor embedding
        //     $table->vector('embedding', 1536);

        //     // Metadata tambahan
        //     $table->string('model')->default('text-embedding-3-small');
        //     $table->integer('dimension')->default(1536);

        //     // Status dan info tambahan (optional tapi berguna)
        //     $table->boolean('is_active')->default(true);
        //     $table->timestamp('last_processed_at')->nullable();

        //     $table->timestamps();
        // });

        // // Index vector untuk similarity search
        // DB::statement('
        //     CREATE INDEX idx_embeddings_vector_cosine
        //     ON embeddings
        //     USING ivfflat (embedding vector_cosine_ops)
        //     WITH (lists = 100);
        // ');

        // // Index tambahan untuk filter cepat
        // Schema::table('embeddings', function (Blueprint $table) {
        //     $table->index(['model']);
        //     $table->index(['is_active']);
        // });
    }

    public function down(): void
    {
        Schema::dropIfExists('embeddings');
        Schema::dropIfExists('ayahs');
        Schema::dropIfExists('surahs');
    }
};
