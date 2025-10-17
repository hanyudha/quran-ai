<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Hapus kolom embedding lama dan buat ulang sebagai vector(1536)
        DB::statement('ALTER TABLE embeddings DROP COLUMN embedding;');
        DB::statement('ALTER TABLE embeddings ADD COLUMN embedding vector(1536);');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE embeddings DROP COLUMN embedding;');
        DB::statement('ALTER TABLE embeddings ADD COLUMN embedding jsonb;');
    }
};
