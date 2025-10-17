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
        //
        Schema::table('ayahs', function (Blueprint $table) {
            if (!Schema::hasColumn('ayahs', 'ayah_in_quran')) {
                $table->integer('ayah_in_quran')->nullable();
            }
            if (!Schema::hasColumn('ayahs', 'ayah_in_surah')) {
                $table->integer('ayah_in_surah')->nullable();
            }
            if (!Schema::hasColumn('ayahs', 'text_ar')) {
                $table->text('text_ar')->nullable();
            }
            if (!Schema::hasColumn('ayahs', 'text_id')) {
                $table->text('text_id')->nullable();
            }
            if (!Schema::hasColumn('ayahs', 'tafsir')) {
                $table->text('tafsir')->nullable();
            }
            if (!Schema::hasColumn('ayahs', 'audio')) {
                $table->jsonb('audio')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
