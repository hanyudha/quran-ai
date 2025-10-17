<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AddAyahsCommand extends Command
{
    protected $signature = 'quran:add-ayahs';
    protected $description = 'Import seluruh ayat Al-Qur’an dari file JSON bersusun (per surah) ke tabel ayahs';

    public function handle()
    {
        $filename = 'quran.json';
        $path = storage_path("app/{$filename}");

        if (!file_exists($path)) {
            $this->error("❌ File tidak ditemukan: {$path}");
            return;
        }

        $json = json_decode(file_get_contents($path), true);
        if (!$json) {
            $this->error('❌ File JSON tidak valid atau rusak.');
            return;
        }

        // Deteksi apakah file berisi list per surah (ada "ayahs") atau list ayat langsung
        $hasNestedStructure = isset($json[0]['ayahs']);

        $this->info($hasNestedStructure
            ? "📖 Struktur bersusun (per surah) terdeteksi. Mengimpor seluruh ayat..."
            : "📖 Struktur flat (langsung ayat) terdeteksi. Mengimpor seluruh ayat...");

        DB::transaction(function () use ($json, $hasNestedStructure) {
            DB::table('ayahs')->truncate();

            $batch = [];
            $count = 0;

            if ($hasNestedStructure) {
                foreach ($json as $surah) {
                    $surahNumber = $surah['number'] ?? null;

                    foreach ($surah['ayahs'] as $ayah) {
                        $batch[] = [
                            'surah'          => $surahNumber,
                            'ayah_in_quran'  => $ayah['number']['inQuran'] ?? null,
                            'ayah_in_surah'  => $ayah['number']['inSurah'] ?? null,
                            'text_ar'        => $ayah['arab'] ?? '',
                            'text_id'        => $ayah['translation'] ?? '',
                            'tafsir'         => isset($ayah['tafsir']) ? json_encode($ayah['tafsir']) : null,
                            'audio'          => isset($ayah['audio']) ? json_encode($ayah['audio']) : null,
                            'image'          => isset($ayah['image']) ? json_encode($ayah['image']) : null,
                            'sajda'          => isset($ayah['meta']['sajda']) ? json_encode($ayah['meta']['sajda']) : null,
                            'juz'            => $ayah['meta']['juz'] ?? null,
                            'page'           => $ayah['meta']['page'] ?? null,
                            'manzil'         => $ayah['meta']['manzil'] ?? null,
                            'ruku'           => $ayah['meta']['ruku'] ?? null,
                            'hizb_quarter'   => $ayah['meta']['hizbQuarter'] ?? null,
                            'created_at'     => now(),
                            'updated_at'     => now(),
                        ];

                        if (count($batch) >= 500) {
                            DB::table('ayahs')->insert($batch);
                            $count += count($batch);
                            $this->info("✅ {$count} ayat diimpor sejauh ini...");
                            $batch = [];
                        }
                    }
                }
            } else {
                // Struktur flat
                foreach ($json as $ayah) {
                    $batch[] = [
                        'surah'          => $ayah['surah'] ?? null,
                        'ayah_in_quran'  => $ayah['ayah_in_quran'] ?? null,
                        'ayah_in_surah'  => $ayah['ayah_in_surah'] ?? null,
                        'text_ar'        => $ayah['text_ar'] ?? '',
                        'text_id'        => $ayah['text_id'] ?? '',
                        'tafsir'         => $ayah['tafsir'] ?? null,
                        'audio'          => isset($ayah['audio']) ? json_encode($ayah['audio']) : null,
                        'image'          => isset($ayah['image']) ? json_encode($ayah['image']) : null,
                        'sajda'          => isset($ayah['sajda']) ? json_encode($ayah['sajda']) : null,
                        'juz'            => $ayah['juz'] ?? null,
                        'page'           => $ayah['page'] ?? null,
                        'manzil'         => $ayah['manzil'] ?? null,
                        'ruku'           => $ayah['ruku'] ?? null,
                        'hizb_quarter'   => $ayah['hizb_quarter'] ?? null,
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ];

                    if (count($batch) >= 500) {
                        DB::table('ayahs')->insert($batch);
                        $count += count($batch);
                        $this->info("✅ {$count} ayat diimpor sejauh ini...");
                        $batch = [];
                    }
                }
            }

            if ($batch) {
                DB::table('ayahs')->insert($batch);
                $count += count($batch);
            }

            $this->info("🎉 Selesai! Total {$count} ayat berhasil diimpor ke tabel `ayahs`.");
        });
    }
}
