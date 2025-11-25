<?php

namespace App\Services\Quran\Chat;

use App\Models\Ayah;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Str;

class QuranChatService
{
    protected $data;

    /**
     * Proses utama: menerima pertanyaan pengguna, mencari ayat semantik, lalu menjawab dengan GPT
     */
    public function processMessage(string $message, ?string $sessionId = null): array
    {
        $sessionId ??= Str::uuid()->toString();

        try {
            Log::info('QuranChatService started', [
                'message' => $message,
                'session_id' => $sessionId
            ]);

            // 1️⃣ Simpan pesan pengguna
            DB::table('chat_histories')->insert([
                'session_id' => $sessionId,
                'role' => 'user',
                'message' => $message,
                'created_at' => now(),
            ]);

            // 2️⃣ Buat embedding untuk pertanyaan
            $response = OpenAI::embeddings()->create([
                'model' => 'text-embedding-3-large',
                'input' => $message,
            ]);

            // Ekstrak embedding dengan aman
            $embedding = $this->extractEmbedding($response);
            Log::debug('Embedding created', [
                'embedding_size' => count($embedding),
                'first_5_values' => array_slice($embedding, 0, 5)
            ]);

            // 3️⃣ Cari ayat paling mirip (berdasarkan semantic search pgvector)
            $results = $this->findSimilarVerses($embedding);

            // Filter hasil dengan ambang batas kemiripan minimal (0.40)
            $results = array_filter($results, fn($r) => $r->similarity >= 0.40);

            Log::info('Similarity results after filtering', [
                'total_results' => count($results),
                'similarities' => collect($results)->pluck('similarity')->toArray()
            ]);

            if (empty($results)) {
                throw new \Exception('Tidak ditemukan ayat yang relevan.');
            }

            // Validasi data ayat
            foreach ($results as $result) {
                if (empty($result->text_ar) || empty($result->text_id)) {
                    Log::warning('Invalid verse data', ['verse_id' => $result->id ?? 'unknown']);
                }
            }

            // 4️⃣ Susun konteks ayat untuk dikirim ke GPT
            $context = collect($results)->map(function ($v) {
                $base = "Surah {$v->surah_name} — Ayat {$v->ayah_in_surah}:\n" .
                    "Teks Arab: {$v->text_ar}\n" .
                    "Terjemahan: {$v->text_id}\n";

                // Tambahkan tafsir jika available
                if (!empty($v->tafsir_text) || !empty($v->tafsir_long) || !empty($v->tafsir_short)) {
                    $tafsirText = $v->tafsir_long ?? $v->tafsir_text ?? $v->tafsir_short;
                    $sourceName = $v->tafsir_source_name ?? 'Tafsir';
                    $base .= "Tafsir ({$sourceName}): {$tafsirText}\n";
                }

                $base .= "------------------------------------------";
                return $base;
            })->join("\n");

            // 5️⃣ Bangun prompt untuk OpenAI
            $prompt = $this->buildPrompt($message, $context);

            Log::info('Prompt dikirim ke OpenAI', ['prompt' => $prompt]);

            // 6️⃣ Panggil OpenAI untuk menghasilkan jawaban alami
            $completion = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' =>
                        "Kamu adalah asisten Qur'an AI yang dibekali tafsir. " .
                            "Jawabanmu HARUS hanya berdasarkan ayat-ayat dan tafsir yang diberikan. " .
                            "Jangan menambah ayat ATAU tafsir dari luar konteks, " .
                            "jangan ubah teks Arab, dan jangan menyebut ayat yang tidak ada dalam konteks. " .
                            "Gunakan informasi tafsir tanpa merubah atau memodifikasi tafsir tersebut. " .
                            "Untuk pertanyaan tentang makna, konteks, atau penjelasan ayat, utamakan menggunakan tafsir yang tersedia. "

                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ],
                ],
                'max_tokens' => 2000,
                'temperature' => 0.3,
            ]);

            // 7️⃣ Ambil hasil jawaban GPT
            $answer = $completion->choices[0]->message->content ?? 'Maaf, tidak ada jawaban yang dihasilkan.';

            // 8️⃣ Simpan jawaban ke histori
            DB::table('chat_histories')->insert([
                'session_id' => $sessionId,
                'role' => 'assistant',
                'message' => $answer,
                'created_at' => now(),
            ]);

            // 9️⃣ Log hasil pencarian
            Log::info('Hasil similarity', collect($results)->map(fn($r) => [
                'surah' => $r->surah_name,
                'ayah' => $r->text_id,
                'similarity' => $r->similarity,
            ])->toArray());

            // 🔟 Kembalikan hasil ke frontend
            return [
                'session_id' => $sessionId,
                'answer' => $answer,
                'context' => $results,
                'similar_verses' => $this->formatVerses($results),
                'success' => true,
            ];
        } catch (\Exception $e) {
            Log::error('QuranChatService Error: ' . $e->getMessage(), [
                'session_id' => $sessionId,
                'message' => $message,
                'trace' => $e->getTraceAsString()
            ]);

            $errorMessage = "Maaf, terjadi kesalahan dalam memproses pertanyaan Anda.";

            DB::table('chat_histories')->insert([
                'session_id' => $sessionId,
                'role' => 'assistant',
                'message' => $errorMessage,
                'created_at' => now(),
            ]);

            return [
                'session_id' => $sessionId,
                'answer' => $errorMessage,
                'context' => [],
                'similar_verses' => [],
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Ekstrak embedding dari respons OpenAI secara aman
     */
    private function extractEmbedding($response): array
    {
        if (isset($response->data[0]->embedding)) {
            return $response->data[0]->embedding;
        }
        if (isset($response->embeddings[0]->embedding)) {
            return $response->embeddings[0]->embedding;
        }

        Log::warning('Struktur embedding tidak dikenal', ['response' => $response]);
        throw new \Exception('Gagal mengekstrak embedding.');
    }

    /**
     * Cari ayat yang paling mirip menggunakan pgvector
     */
    private function findSimilarVerses(array $embedding): array
    {
        try {
            $embeddingString = $this->formatEmbeddingForVector($embedding);

            $results = DB::select("
                SELECT
                    a.id, 
                    a.text_ar, 
                    a.text_id,
                    a.ayah_in_surah,
                    s.name_id AS surah_name,
                    ts.name AS tafsir_source_name,
                    ts.code AS tafsir_source_code,
                    t.short_text AS tafsir_short,
                    t.long_text AS tafsir_long,
                    1 - (e.embedding <=> ?) AS similarity
                FROM embeddings e
                JOIN ayahs a ON a.id = e.ayah_id
                JOIN surahs s ON s.id = a.surah_id
                LEFT JOIN tafsirs t ON t.ayah_id = a.id AND t.language = 'id'
                LEFT JOIN tafsir_sources ts ON ts.id = t.source_id
                WHERE e.embedding IS NOT NULL
                AND a.text_ar IS NOT NULL
                AND a.text_ar !=''
                ORDER BY e.embedding <=> ?
                LIMIT 3
            ", [$embeddingString, $embeddingString]);

            return $results;
        } catch (\Exception $e) {
            Log::error('findSimilarVerses (pgvector) failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Format array embedding ke string format PostgreSQL [x,y,z]
     */
    private function formatEmbeddingForVector(array $embedding): string
    {
        // Pastikan semua nilai adalah float dan format dengan presisi yang cukup
        $formatted = array_map(function ($value) {
            return is_numeric($value) ? (float)$value : 0.0;
        }, $embedding);

        return '[' . implode(',', $formatted) . ']';
    }

    /**
     * Bangun prompt untuk OpenAI berdasarkan konteks ayat
     */
    private function buildPrompt(string $question, string $context): string
    {
        return <<<PROMPT
Pertanyaan pengguna:
{$question}

Ayat-ayat Al-Qur'an hasil pencarian semantik:
{$context}

Instruksi:
1. Jawablah HANYA berdasarkan ayat-ayat dan tafsir di atas.
2. Untuk pertanyaan tentang makna, penjelasan, atau konteks ayat, GUNAKAN informasi tafsir yang tersedia.
3. Sertakan nama surah dan nomor ayat sesuai teks di atas.
4. Jika tersedia tafsir, jelaskan makna ayat berdasarkan tafsir tersebut.
5. Tampilkan nama surah dan nomor ayat di awal setiap kutipan.
6. Jangan menambahkan ayat atau tafsir dari luar konteks di atas.
7. Prioritaskan penjelasan dari tafsir yang tersedia untuk pertanyaan mendalam.

Format jawaban yang diharapkan:
- Kutip ayat yang relevan
- Berikan penjelasan berdasarkan tafsir
- Sertakan sumber tafsir jika tersedia


PROMPT;
    }

    /**
     * Format ayat untuk respon API
     */
    // private function formatVerses(array $verses): array
    // {
    //     return collect($verses)->map(function ($verse) {
    //         return [
    //             'surah_name' => $verse->surah_name ?? 'Unknown',
    //             'verse_number' => $verse->text_id ?? 0,
    //             'verse_test' => (string) ($verse->ayah_in_surah ?? 'N/A'), // Now using actual ayah numb
    //             'arabic_text' => $verse->text_ar ?? '',
    //             'similarity' => isset($verse->similarity)
    //                 ? round($verse->similarity * 100, 2) . '%'
    //                 : 'N/A',
    //             'ayah_in_surah' => $verse->ayah_in_surah ?? null, // Actual ayah number from DB
    //         ];
    //     })->toArray();
    // }

    /**
     * Format ayat untuk respon API dengan tafsir
     */
    private function formatVerses(array $verses): array
    {
        return collect($verses)->map(function ($verse) {
            $formatted = [
                'surah_name' => $verse->surah_name ?? 'Unknown',
                'verse_number' => $verse->text_id ?? 0,
                'arabic_text' => $verse->text_ar ?? '',
                // 'translation' => $verse->text_id ?? '',
                'similarity' => isset($verse->similarity)
                    ? round($verse->similarity * 100, 2) . '%'
                    : 'N/A',
                'ayah_in_surah' => $verse->ayah_in_surah ?? null, // Actual ayah number from DB
            ];

            // Add tafsir if available
            $tafsirText = $verse->tafsir_long ?? $verse->tafsir_text ?? $verse->tafsir_short ?? null;
            if (!empty($tafsirText)) {
                $formatted['tafsir'] = [
                    'source' => $verse->tafsir_source_name ?? $verse->tafsir_source_code ?? 'Unknown',
                    'text' => $tafsirText,
                    'has_long_text' => !empty($verse->tafsir_long),
                ];
            }

            return $formatted;
        })->toArray();
    }

    /**
     * Ambil histori chat
     */
    public function getChatHistory(string $sessionId): array
    {
        return DB::table('chat_histories')
            ->where('session_id', $sessionId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->toArray();
    }

    /**
     * Bersihkan histori chat
     */
    public function clearChatHistory(string $sessionId): bool
    {
        return DB::table('chat_histories')
            ->where('session_id', $sessionId)
            ->delete() > 0;
    }
}
