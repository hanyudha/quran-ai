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

        $dimension = 3072; // OpenAI text-embedding-3-large

        Schema::create('tafsir_embeddings', function (Blueprint $table) use ($dimension) {
            $table->id();

            $table->foreignId('tafsir_id')
                    ->constrained('tafsirs')
                    ->onDelete('cascade');
            $table->vector('embedding', $dimension); // text-embedding-3-large
            $table->timestamps();
        });

    // Tidak membuat index karena dimensi > 2000 tidak didukung karena pgvector di Windows bukan build resmi.
        echo "⚠️ Skipping vector index creation (dimension={$dimension} > 2000 not supported by HNSW/IVFFLAT)\n";
    }

    public function down(): void
    {
        Schema::dropIfExists('tafsir_embeddings');
    }
};
