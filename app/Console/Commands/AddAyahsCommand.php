<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Surah;
use App\Models\Ayah;

class AddAyahsCommand extends Command
{
    protected $signature = 'quran:add-ayahs';
    protected $description = 'Import Quran JSON into Surah and Ayah tables';

    public function handle()
    {
        $path = storage_path('app/quran.json');

        if (!file_exists($path)) {
            $this->error("❌ File tidak ditemukan: {$path}");
            return;
        }

        $this->info("📖 Membaca file Quran JSON...");
        $json = json_decode(file_get_contents($path), true);

        if (!is_array($json)) {
            $this->error("❌ Format JSON tidak valid!");
            return;
        }

        $totalSurah = count($json);
        $this->info("📚 Terdeteksi {$totalSurah} surah. Mulai impor...");

        $ayahCount = 0;

        foreach ($json as $index => $surahData) {
            $surahNumber = $surahData['number'] ?? null;

            // 🕌 Simpan data surah
            $surah = Surah::updateOrCreate(
                ['number' => $surahNumber],
                [
                    'name_ar' => $surahData['name_ar'] ?? null,
                    'name_id' => $surahData['name'] ?? null,
                    'translation' => $surahData['translation'] ?? null,
                    'revelation' => $surahData['revelation'] ?? null,
                    'number_of_ayahs' => $surahData['numberOfAyahs'] ?? null,
                    'description' => $surahData['description'] ?? null,
                    'audio' => $surahData['audio'] ?? null,
                    'bismillah_ar' => $surahData['bismillah']['arab'] ?? null,
                    'bismillah_id' => $surahData['bismillah']['translation'] ?? null,
                    'bismillah_audio' => $surahData['bismillah']['audio'] ?? null,
                ]
            );

            // 🕋 Simpan semua ayat dari surah
            if (!empty($surahData['ayahs'])) {
                foreach ($surahData['ayahs'] as $ayahData) {
                    Ayah::updateOrCreate(
                        ['ayah_in_quran' => $ayahData['number']['inQuran'] ?? null],
                        [
                            'surah_id' => $surah->id,
                            'ayah_in_surah' => $ayahData['number']['inSurah'] ?? null,
                            'text_ar' => $ayahData['arab'] ?? '',
                            'text_id' => $ayahData['translation'] ?? '',
                            'audio' => $ayahData['audio'] ?? null,
                            'image' => $ayahData['image'] ?? null,
                            'tafsir' => $ayahData['tafsir'] ?? null,
                            'meta' => $ayahData['meta'] ?? null,
                        ]
                    );

                    $ayahCount++;
                    if ($ayahCount % 500 == 0) {
                        $this->info("✅ {$ayahCount} ayat diimpor sejauh ini...");
                    }
                }
            }

            $this->info("📗 Surah {$surahNumber}: {$surahData['name']} selesai diimpor.");
        }

        $this->info("🎉 Impor selesai! Total {$ayahCount} ayat dari {$totalSurah} surah berhasil dimasukkan.");
    }
}
