<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Hapus data duplikat jika ada (keep the earliest one)
        DB::statement('
            DELETE FROM tafsir_embeddings 
            WHERE id NOT IN (
                SELECT MIN(id) 
                FROM tafsir_embeddings 
                GROUP BY tafsir_id
            )
        ');

        // Tambahkan unique constraint jika belum ada
        Schema::table('tafsir_embeddings', function (Blueprint $table) {
            // Cek dulu apakah constraint sudah ada
            $constraints = DB::select("
                SELECT constraint_name 
                FROM information_schema.table_constraints 
                WHERE table_name = 'tafsir_embeddings' 
                AND constraint_type = 'UNIQUE'
            ");
            
            if (empty($constraints)) {
                $table->unique('tafsir_id');
            }
        });
    }

    public function down()
    {
        Schema::table('tafsir_embeddings', function (Blueprint $table) {
            $table->dropUnique(['tafsir_id']);
        });
    }
};