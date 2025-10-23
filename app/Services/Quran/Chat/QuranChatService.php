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

            // 3️⃣ Cari ayat paling mirip (berdasarkan semantic search pgvector)
            $results = $this->findSimilarVerses($embedding);

            // Filter hasil dengan ambang batas kemiripan minimal (0.40)
            $results = array_filter($results, fn($r) => $r->similarity >= 0.40);

            if (empty($results)) {
                throw new \Exception('Tidak ditemukan ayat yang relevan.');
            }

            // 4️⃣ Susun konteks ayat untuk dikirim ke GPT
            $context = collect($results)->map(fn($v) =>
                "Surah {$v->surah_name} — Ayat {$v->text_id}:\n" .
                "Teks Arab: {$v->text_ar}\n" .
                "Terjemahan: {$v->text_id}\n" .
                "------------------------------------------"
            )->join("\n");

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
                            "Kamu adalah asisten Qur'an AI. Jawabanmu HARUS hanya berdasarkan ayat-ayat "
                            . "yang diberikan di bawah ini. Jangan menambah ayat dari luar konteks, "
                            . "jangan ubah teks Arab, dan jangan menyebut ayat yang tidak ada dalam konteks."
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ],
                ],
                'max_tokens' => 1000,
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
                SELECT a.id, a.text_ar, a.text_id, s.name_id AS surah_name,
                       1 - (e.embedding <=> ?) AS similarity
                FROM embeddings e
                JOIN ayahs a ON a.id = e.ayah_id
                JOIN surahs s ON s.id = a.surah_id
                ORDER BY e.embedding <=> ?
                LIMIT 10
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
        return '[' . implode(',', $embedding) . ']';
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
1. Jawablah HANYA berdasarkan ayat-ayat di atas.
2. Jika ayat dengan nomor atau nama surah tertentu disebutkan dalam pertanyaan, utamakan menampilkan ayat itu secara langsung
3. Sertakan nama surah dan nomor ayat sesuai teks di atas.
4. Jika tidak ditemukan, pilih ayat yang paling semantik relevan.
5. Tampilkan nama surah dan nomor ayat di awal setiap kutipan.
6. Jangan menambahkan ayat atau informasi dari luar konteks di atas.

PROMPT;
    }

    /**
     * Format ayat untuk respon API
     */
    private function formatVerses(array $verses): array
    {
        return collect($verses)->map(function ($verse) {
            return [
                'surah_name' => $verse->surah_name,
                'verse_number' => $verse->text_id,
                'arabic_text' => $verse->text_ar,
                'similarity' => isset($verse->similarity)
                    ? round($verse->similarity * 100, 2) . '%'
                    : 'N/A',
            ];
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
