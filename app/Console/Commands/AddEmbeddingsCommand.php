<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ayah;
use App\Models\Embedding;
use OpenAI;
use Exception;

class AddEmbeddingsCommand extends Command
{
    protected $signature = 'quran:add-embeddings {--limit=100 : Jumlah ayat per batch}';
    protected $description = 'Generate OpenAI embeddings untuk setiap ayat Al-Quran';

    public function handle()
    {
        $apiKey = env('OPENAI_API_KEY');
        if (!$apiKey) {
            $this->error('❌ OPENAI_API_KEY belum diatur di .env');
            return;
        }

        $client = OpenAI::client($apiKey);
        $limit = (int) $this->option('limit');

        $ayahs = Ayah::whereDoesntHave('embedding')
            ->limit($limit)
            ->get();

        if ($ayahs->isEmpty()) {
            $this->info('✅ Semua ayat sudah memiliki embedding.');
            return;
        }

        $this->info("🧠 Membuat embedding untuk {$ayahs->count()} ayat...");

        foreach ($ayahs as $ayah) {
            try {
                // Gabungkan teks Arab dan terjemahan agar lebih bermakna semantik
                $text = trim($ayah->text_ar . "\n" . $ayah->text_id);

                // Panggil API OpenAI
                $response = $client->embeddings()->create([
                    'model' => 'text-embedding-3-small',
                    'input' => $text,
                ]);

                $vector = $response->embeddings[0]->embedding ?? null;

                if ($vector) {
                    // Embedding::updateOrCreate(
                    //     ['ayah_id' => $ayah->id],
                    //     ['embedding' => $vector]
                    // );
                    Embedding::storeVector($ayah->id, $vector);//diganti dengan ini
                    $this->line("✅ [{$ayah->id}] Surah {$ayah->surah->number} Ayat {$ayah->ayah_in_surah}");
                } else {
                    $this->warn("⚠️ Gagal membuat embedding untuk ayat ID {$ayah->id}");
                }

                // Delay kecil untuk menghindari rate-limit
                usleep(250000); // 0.25 detik

            } catch (Exception $e) {
                $this->error("❌ Error pada ayat ID {$ayah->id}: " . $e->getMessage());
            }
        }

        $this->info('🎉 Proses embedding selesai.');
    }
}
