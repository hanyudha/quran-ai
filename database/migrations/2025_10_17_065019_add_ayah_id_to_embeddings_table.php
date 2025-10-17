<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('embeddings', function (Blueprint $table) {
            //
            $table->unsignedBigInteger('ayah_id')->nullable()->after('id');
            // jika kamu punya tabel `ayahs`, bisa aktifkan relasi ini
            // $table->foreign('ayah_id')->references('id')->on('ayahs')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('embeddings', function (Blueprint $table) {
            //
            $table->dropColumn('ayah_id');
        });
    }
};
