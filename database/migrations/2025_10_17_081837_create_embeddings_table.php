<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Pastikan ekstensi pgvector aktif
        DB::statement('CREATE EXTENSION IF NOT EXISTS vector;');

        Schema::create('embeddings', function (Blueprint $table) {
            $table->id();

            // Relasi ke ayahs
            $table->foreignId('ayah_id')
                ->constrained('ayahs')
                ->onDelete('cascade');

            // Vektor embedding (1536 dim untuk text-embedding-3-small)
            $table->vector('embedding', 1536);

            $table->timestamps();
        });

        // Index untuk similarity search cepat
        DB::statement('CREATE INDEX idx_embeddings_vector_cosine ON embeddings USING ivfflat (embedding vector_cosine_ops) WITH (lists = 100);');
    }

    public function down(): void
    {
        Schema::dropIfExists('embeddings');
    }
};
