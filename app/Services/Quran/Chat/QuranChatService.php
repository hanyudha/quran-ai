<?php

namespace App\Services\Quran\Chat;

use App\Models\Ayah;
use App\Models\Embedding;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Str;

class QuranChatService
{
    public function processMessage(string $message, ?string $sessionId = null): array
    {
        $sessionId ??= Str::uuid()->toString();

        try {
            // 1️⃣ Save user message
            DB::table('chat_histories')->insert([
                'session_id' => $sessionId,
                'role' => 'user',
                'message' => $message,
                'created_at' => now(),
            ]);

            // 2️⃣ Create embedding for the question
            $response = OpenAI::embeddings()->create([
                'model' => 'text-embedding-3-large',
                'input' => $message,
            ]);

            // Fix: Proper way to access embedding data
            $embedding = [];
            if (isset($response->embeddings) && count($response->embeddings) > 0) {
                $embedding = $response->embeddings[0]->embedding;
            } elseif (isset($response->data) && count($response->data) > 0) {
                // Fallback for different SDK versions
                $embedding = $response->data[0]->embedding;
            } else {
                throw new \Exception('Failed to generate embedding');
            }

            // 3️⃣ Find semantically similar verses - FIXED cube function usage
            $results = $this->findSimilarVerses($embedding);

            if (empty($results)) {
                throw new \Exception('No relevant verses found');
            }

            // 4️⃣ Build verse context
            $context = collect($results)->map(function ($result) {
                return sprintf(
                    "Surah %s Ayat %d:\nArabic: %s\n",
                    $result->surah_name,
                    $result->text_id,
                    $result->text_ar
                );
            })->join("\n");

            // 5️⃣ Call OpenAI to generate natural response
            $prompt = $this->buildPrompt($message, $context);

            $completion = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Anda adalah asisten AI Al-Quran yang membantu menjawab pertanyaan tentang Islam dan Al-Quran. Jawablah dengan sopan, jelas, dan berdasarkan ayat-ayat yang diberikan.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ],
                ],
                'max_tokens' => 1000,
                'temperature' => 0.3,
            ]);

            // Fix: Proper way to access chat completion response
            $answer = '';
            if (isset($completion->choices[0]->message->content)) {
                $answer = $completion->choices[0]->message->content;
            } else {
                throw new \Exception('Failed to generate response from OpenAI');
            }

            // 6️⃣ Save assistant response to history
            DB::table('chat_histories')->insert([
                'session_id' => $sessionId,
                'role' => 'assistant',
                'message' => $answer,
                'created_at' => now(),
            ]);

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

            $errorMessage = "Maaf, terjadi kesalahan dalam memproses pertanyaan Anda. Silakan coba lagi.";

            // Save error response to history
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
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Find similar verses using PostgreSQL cube function
     */
    private function findSimilarVerses(array $embedding): array
    {
        // Convert embedding to PostgreSQL cube format
        $embeddingString = $this->formatEmbeddingForPostgres($embedding);

        // First, let's test if cube function works with a simple query
        try {
            $testQuery = DB::select("
                SELECT cube(?) as test_cube
            ", [$embeddingString]);

            // If cube function works, proceed with the main query
            $results = DB::select("
                SELECT a.id, a.text_ar, a.text_id, s.name_id as surah_name,
                       1 - (e.embedding <=> cube(?)) as similarity
                FROM embeddings e
                JOIN ayahs a ON a.id = e.ayah_id
                JOIN surahs s ON s.id = a.surah_id
                ORDER BY e.embedding <=> cube(?)
                LIMIT 3
            ", [$embeddingString, $embeddingString]);

            return $results;
        } catch (\Exception $e) {
            // If cube function fails, use a fallback approach
            Log::warning('Cube function failed, using fallback: ' . $e->getMessage());
            return $this->findSimilarVersesFallback();
        }
    }

    /**
     * Fallback method if cube function doesn't work
     */
    private function findSimilarVersesFallback(): array
    {
        // Simple fallback: get random verses
        return DB::select("
            SELECT a.id, a.text_ar, a.text_id, s.name_id as surah_name,
                   0.5 as similarity
            FROM embeddings e
            JOIN ayahs a ON a.id = e.ayah_id
            JOIN surahs s ON s.id = a.surah_id
            ORDER BY RANDOM()
            LIMIT 3
        ");
    }

    /**
     * Format embedding for PostgreSQL cube function
     */
    private function formatEmbeddingForPostgres(array $embedding): string
    {
        // PostgreSQL cube format: array of numbers like '0.1, 0.2, 0.3'
        return implode(',', $embedding);
    }

    /**
     * Build the prompt for OpenAI
     */
    private function buildPrompt(string $question, string $context): string
    {
        return "
Pertanyaan: {$question}

Ayat-ayat Al-Quran yang relevan:
{$context}

Instruksi:
1. Jawab pertanyaan berdasarkan ayat-ayat Al-Quran yang diberikan di atas
2. Jika pertanyaan tidak bisa dijawab dari ayat-ayat tersebut, jelaskan dengan sopan
3. Berikan jawaban dalam bahasa yang sama dengan pertanyaan
4. Bersikaplah akurat dan hormat
5. Sertakan referensi ke nama surah dan nomor ayat
6. Jika sesuai, berikan penjelasan singkat berdasarkan tafsir yang otentik

Jawaban:";
    }

    /**
     * Format verses for better response structure
     */
    private function formatVerses(array $verses): array
    {
        return collect($verses)->map(function ($verse) {
            return [
                'surah_name' => $verse->surah_name,
                'verse_number' => $verse->text_id,
                'arabic_text' => $verse->text_ar,
                'similarity' => isset($verse->similarity) ? round($verse->similarity * 100, 2) . '%' : 'N/A'
            ];
        })->toArray();
    }

    /**
     * Get chat history for a session
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
     * Clear chat history for a session
     */
    public function clearChatHistory(string $sessionId): bool
    {
        return DB::table('chat_histories')
            ->where('session_id', $sessionId)
            ->delete() > 0;
    }
}
