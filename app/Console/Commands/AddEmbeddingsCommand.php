<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Ayah;

class AddEmbeddingsCommand extends Command
{
    protected $signature = 'quran:add-embeddings 
                            {--offset=0 : Mulai dari ayat keberapa}
                            {--limit=100 : Jumlah ayat yang diproses}
                            {--model=text-embedding-3-small : Model embedding yang digunakan}';

    protected $description = 'Membuat dan menyimpan embedding untuk ayat-ayat Al-Qur’an ke database.';

    public function handle(): void
    {
        $offset = (int) $this->option('offset');
        $limit = (int) $this->option('limit');
        $model = $this->option('model');

        $this->info("🧠 Membuat embedding untuk {$limit} ayat (offset {$offset}) menggunakan model: {$model}");

        // --- Ambil jumlah dimensi dari tabel embeddings
        $tableDim = $this->getEmbeddingDimension();
        $this->line("📐 Tabel embeddings memiliki dimensi: {$tableDim}");

        // --- Tentukan dimensi model berdasarkan nama
        $modelDim = str_contains($model, 'large') ? 3072 : 1536;
        $this->line("🧩 Model {$model} menghasilkan dimensi: {$modelDim}");

        // --- Peringatan bila tidak cocok
        if ($tableDim !== $modelDim) {
            $this->warn("⚠️ WARNING: Dimensi tabel ({$tableDim}) ≠ model ({$modelDim}).
Disarankan jalankan ulang migrasi agar sesuai, atau gunakan model yang cocok.");
        }

        // --- Ambil ayat dari DB
        $ayahs = Ayah::skip($offset)->take($limit)->get();
        if ($ayahs->isEmpty()) {
            $this->warn('Tidak ada ayat ditemukan.');
            return;
        }

        $client = app(\OpenAI\Client::class);
        $processed = 0;

        foreach ($ayahs as $ayah) {
            $text = trim(($ayah->text_ar ?? '') . ' ' . ($ayah->text_id ?? ''));

            if (strlen($text) < 10) {
                $this->warn("⚠️ Ayat ID {$ayah->id} dilewati (teks terlalu pendek).");
                continue;
            }

            try {
                $response = $client->embeddings()->create([
                    'model' => $model,
                    'input' => $text,
                ]);

                $vector = $response->embeddings[0]->embedding ?? null;

                if ($vector) {
                    // Pastikan dimensi cocok
                    if (count($vector) !== $tableDim) {
                        $this->error("❌ Error: dimensi embedding (" . count($vector) . ") ≠ tabel ({$tableDim}) untuk ayat ID {$ayah->id}");
                        continue;
                    }

                    $vectorString = '[' . implode(',', $vector) . ']';

                    DB::statement('
                        INSERT INTO embeddings (ayah_id, embedding, created_at, updated_at)
                        VALUES (?, ?::vector, NOW(), NOW())
                        ON CONFLICT (ayah_id)
                        DO UPDATE SET embedding = EXCLUDED.embedding, updated_at = NOW()
                    ', [$ayah->id, $vectorString]);

                    $processed++;
                    $this->line("✅ [{$ayah->id}] Surah {$ayah->surah_id} Ayat {$ayah->ayah_in_surah}");
                } else {
                    $this->warn("⚠️ Tidak ada data embedding untuk ayat ID {$ayah->id}");
                }
            } catch (\Throwable $e) {
                $this->error("❌ Error pada ayat ID {$ayah->id}: {$e->getMessage()}");
                continue;
            }
        }

        $this->info("🎉 Selesai! Total embedding berhasil disimpan: {$processed}");
    }

    /**
     * Ambil jumlah dimensi kolom vector dari tabel embeddings secara akurat.
     */
    protected function getEmbeddingDimension(): int
    {
        try {
            $result = DB::selectOne("
            SELECT
                format_type(a.atttypid, a.atttypmod) AS columntype
            FROM pg_attribute a
            JOIN pg_class c ON a.attrelid = c.oid
            WHERE c.relname = 'embeddings'
              AND a.attname = 'embedding'
              AND a.attnum > 0
            LIMIT 1;
        ");

            if ($result && isset($result->columntype)) {
                // contoh hasil: "vector(3072)"
                if (preg_match('/vector\((\d+)\)/', $result->columntype, $matches)) {
                    $dimension = (int) $matches[1];
                    $this->info("✅ Metadata PostgreSQL terdeteksi: vector({$dimension})");
                    return $dimension;
                }
            }

            $this->warn("⚠️ Tidak menemukan definisi kolom vector pada format_type(), fallback ke 1536.");
        } catch (\Throwable $e) {
            $this->warn("Gagal membaca definisi tabel embeddings: {$e->getMessage()}");
        }

        return 1536; // fallback aman
    }
}
