<?php
// database/seeders/TafsirSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class TafsirSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing data
        Schema::disableForeignKeyConstraints();
        DB::table('tafsirs')->truncate();
        Schema::enableForeignKeyConstraints();

        // Load Quran JSON data
        //$jsonPath = database_path('data/quran.json');
        $jsonPath = storage_path('app/quran.json');
        
        if (!File::exists($jsonPath)) {
            $this->command->error("quran.json file not found at: {$jsonPath}");
            return;
        }

        $quranData = json_decode(File::get($jsonPath), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->command->error('Invalid JSON format in quran.json: ' . json_last_error_msg());
            return;
        }

        $tafsirData = [];
        $processedCount = 0;

        foreach ($quranData as $surah) {
            if (!isset($surah['ayahs']) || !is_array($surah['ayahs'])) {
                continue;
            }

            foreach ($surah['ayahs'] as $ayah) {
                $ayahId = $ayah['number']['inQuran'];
                
                // Get source IDs
                $kemenagId = DB::table('tafsir_sources')->where('code', 'kemenag')->value('id');
                $quraishId = DB::table('tafsir_sources')->where('code', 'quraish')->value('id');
                $jalalaynId = DB::table('tafsir_sources')->where('code', 'jalalayn')->value('id');

                // Process Kemenag tafsir
                if (isset($ayah['tafsir']['kemenag']) && $kemenagId) {
                    $tafsirData[] = [
                        'ayah_id' => $ayahId,
                        'source_id' => $kemenagId,
                        'short_text' => $ayah['tafsir']['kemenag']['short'] ?? null,
                        'long_text' => $ayah['tafsir']['kemenag']['long'] ?? null,
                        'language' => 'id',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                // Process Quraish tafsir
                // if (isset($ayah['tafsir']['quraish']) && $quraishId) {
                //     $tafsirData[] = [
                //         'ayah_id' => $ayahId,
                //         'source_id' => $quraishId,
                //         'short_text' => $ayah['tafsir']['quraish'],
                //         'long_text' => null,
                //         'language' => 'id',
                //         'created_at' => now(),
                //         'updated_at' => now(),
                //     ];
                // }

                // Process Jalalayn tafsir
                // if (isset($ayah['tafsir']['jalalayn']) && $jalalaynId) {
                //     $tafsirData[] = [
                //         'ayah_id' => $ayahId,
                //         'source_id' => $jalalaynId,
                //         'short_text' => $ayah['tafsir']['jalalayn'],
                //         'long_text' => null,
                //         'language' => 'id',
                //         'created_at' => now(),
                //         'updated_at' => now(),
                //     ];
                // }

                $processedCount++;

                // Insert in chunks to avoid memory issues
                if (count($tafsirData) >= 100) {
                    try {
                        DB::table('tafsirs')->insert($tafsirData);
                        $tafsirData = [];
                    } catch (\Exception $e) {
                        $this->command->error("Error inserting batch: " . $e->getMessage());
                    }
                }
            }
        }

        // Insert remaining data
        if (!empty($tafsirData)) {
            try {
                DB::table('tafsirs')->insert($tafsirData);
            } catch (\Exception $e) {
                $this->command->error("Error inserting final batch: " . $e->getMessage());
            }
        }

        $this->command->info("Successfully processed {$processedCount} ayahs and imported tafsir data!");
        $this->command->info("Total tafsir records inserted: " . count($tafsirData));
    }
}