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

        $dimension = 3072;

        Schema::create('embeddings', function (Blueprint $table) use ($dimension) {
            $table->id();

            $table->foreignId('ayah_id')
                ->constrained('ayahs')
                ->onDelete('cascade');

            $table->vector('embedding', $dimension);

            $table->timestamps();

            $table->unique('ayah_id');
        });

        // Tidak membuat index karena dimensi > 2000 tidak didukung
        echo "⚠️ Skipping vector index creation (dimension={$dimension} > 2000 not supported by HNSW/IVFFLAT)\n";
    }

    public function down(): void
    {
        Schema::dropIfExists('embeddings');
    }
};
