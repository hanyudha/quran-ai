<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('ALTER TABLE embeddings ADD CONSTRAINT embeddings_ayah_id_unique UNIQUE (ayah_id)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE embeddings DROP CONSTRAINT IF EXISTS embeddings_ayah_id_unique');
    }
};
