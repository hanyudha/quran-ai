<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Tafsir;

class AddTafsirEmbeddingsCommand extends Command
{
    protected $signature = 'quran:add-tafsir-embeddings 
                            {--offset=0 : Mulai dari tafsir keberapa}
                            {--limit=100 : Jumlah tafsir yang diproses}
                            {--model=text-embedding-3-large : Model embedding yang digunakan}
                            {--source= : Filter by source code (kemenag, quraish, jalalayn)}';

    protected $description = 'Membuat dan menyimpan embedding untuk tafsir Al-Qur\'an ke database.';

    public function handle(): void
    {
        $offset = (int) $this->option('offset');
        $limit = (int) $this->option('limit');
        $model = $this->option('model');
        $sourceCode = $this->option('source');

        $this->info("🧠 Membuat embedding untuk {$limit} tafsir (offset {$offset}) menggunakan model: {$model}");

        // --- Ambil jumlah dimensi dari tabel tafsir_embeddings
        $tableDim = $this->getEmbeddingDimension('tafsir_embeddings');
        $this->line("📐 Tabel tafsir_embeddings memiliki dimensi: {$tableDim}");

        // --- Tentukan dimensi model berdasarkan nama
        $modelDim = str_contains($model, 'large') ? 3072 : 1536;
        $this->line("🧩 Model {$model} menghasilkan dimensi: {$modelDim}");

        // --- Peringatan bila tidak cocok
        if ($tableDim !== $modelDim) {
            $this->warn("⚠️ WARNING: Dimensi tabel ({$tableDim}) ≠ model ({$modelDim}).
Disarankan jalankan ulang migrasi agar sesuai, atau gunakan model yang cocok.");
        }

        // --- Query tafsirs dengan relasi
        $query = Tafsir::with(['source', 'ayah.surah']);
        
        if ($sourceCode) {
            $query->whereHas('source', function($q) use ($sourceCode) {
                $q->where('code', $sourceCode);
            });
            $this->line("🔍 Filter by source: {$sourceCode}");
        }

        $tafsirs = $query->skip($offset)->take($limit)->get();
        
        if ($tafsirs->isEmpty()) {
            $this->warn('Tidak ada tafsir ditemukan.');
            return;
        }

        $client = app(\OpenAI\Client::class);
        $processed = 0;

        foreach ($tafsirs as $tafsir) {
            // Prioritize long_text, fallback to short_text
            $text = $tafsir->long_text ?? $tafsir->short_text;
            
            if (empty($text)) {
                $this->warn("⚠️ Tafsir ID {$tafsir->id} dilewati (teks kosong).");
                continue;
            }

            // Truncate if too long (OpenAI limit ~8192 tokens)
            // if (strlen($text) > 6000) {
            //     $text = substr($text, 0, 6000) . '...';
            //     $this->line("📝 Tafsir ID {$tafsir->id} dipotong menjadi 6000 karakter");
            // }

            try {
                $response = $client->embeddings()->create([
                    'model' => $model,
                    'input' => $text,
                ]);

                $vector = $response->embeddings[0]->embedding ?? null;

                if ($vector) {
                    // Pastikan dimensi cocok
                    if (count($vector) !== $tableDim) {
                        $this->error("❌ Error: dimensi embedding (" . count($vector) . ") ≠ tabel ({$tableDim}) untuk tafsir ID {$tafsir->id}");
                        continue;
                    }

                    $vectorString = '[' . implode(',', $vector) . ']';

                    DB::statement('
                        INSERT INTO tafsir_embeddings (tafsir_id, embedding, created_at, updated_at)
                        VALUES (?, ?::vector, NOW(), NOW())
                        ON CONFLICT (tafsir_id)
                        DO UPDATE SET embedding = EXCLUDED.embedding, updated_at = NOW()
                    ', [$tafsir->id, $vectorString]);

                    $processed++;
                    $this->line("✅ [{$tafsir->id}] {$tafsir->source->name} - Surah {$tafsir->ayah->surah->name_id} Ayat {$tafsir->ayah->ayah_in_surah}");
                } else {
                    $this->warn("⚠️ Tidak ada data embedding untuk tafsir ID {$tafsir->id}");
                }
            } catch (\Throwable $e) {
                $this->error("❌ Error pada tafsir ID {$tafsir->id}: {$e->getMessage()}");
                continue;
            }
        }

        $this->info("🎉 Selesai! Total tafsir embedding berhasil disimpan: {$processed}");
    }

    /**
     * Ambil jumlah dimensi kolom vector dari tabel embeddings secara akurat.
     */
    protected function getEmbeddingDimension(string $tableName = 'embeddings'): int
    {
        try {
            $result = DB::selectOne("
            SELECT
                format_type(a.atttypid, a.atttypmod) AS columntype
            FROM pg_attribute a
            JOIN pg_class c ON a.attrelid = c.oid
            WHERE c.relname = ?
              AND a.attname = 'embedding'
              AND a.attnum > 0
            LIMIT 1;
        ", [$tableName]);

            if ($result && isset($result->columntype)) {
                if (preg_match('/vector\((\d+)\)/', $result->columntype, $matches)) {
                    $dimension = (int) $matches[1];
                    $this->info("✅ Metadata PostgreSQL terdeteksi: vector({$dimension})");
                    return $dimension;
                }
            }

            $this->warn("⚠️ Tidak menemukan definisi kolom vector pada format_type(), fallback ke 3072.");
        } catch (\Throwable $e) {
            $this->warn("Gagal membaca definisi tabel {$tableName}: {$e->getMessage()}");
        }

        // Default fallback: 3072 for tafsir_embeddings, 1536 for embeddings
        return $tableName === 'tafsir_embeddings' ? 3072 : 1536;
    }
}