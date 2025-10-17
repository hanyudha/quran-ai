<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Embedding;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class QuranSemanticSearch extends Command
{
    protected $signature = 'quran:semantic-search {query}';
    protected $description = 'Search ayat semantically using embeddings';

    // public function handle()
    // {
    //     $query = $this->argument('query');
    //     //$apiKey = env('OPENAI_API_KEY');
    //     $apiKey = config('services.openai.api_key');

    //     //debug: Check if apiKey is loaded correctly
    //     $this->info("API Key: " . ($apiKey ? substr($apiKey, 0, 8) . '...' : 'Not Found'));

    //     if (!$apiKey) {
    //         $this->error('❌ OPENAI_API_KEY is not configured in config/services.php');
    //         return;
    //     }

    //     // 1️⃣ Buat embedding dari pertanyaan
    //     $response = Http::withToken($apiKey)
    //         ->post('https://api.openai.com/v1/embeddings', [
    //             'model' => 'text-embedding-3-small',
    //             'input' => $query,
    //         ]);

    //     if ($response->failed()) {
    //         $this->error("Failed to generate embedding for query");
    //         $this->line("Status: " . $response->status());
    //         $this->line("Response: " . $response->body());
    //         return;
    //     }

    //     $queryVector = $response->json('data.0.embedding');
    //     if (!$queryVector) {
    //         $this->error("No embedding vector received from OpenAI");
    //         return;
    //     }

    //     $vectorString = '[' . implode(',', $queryVector) . ']';

    //     // 2️⃣ Cari embedding terdekat di database
    //     $results = DB::table('embeddings')
    //         ->select('id', 'ayah_id') // versi chatgpt
    //         ->selectRaw('(1 - (embedding <=> ?)) as similarity', [$vectorString])
    //         ->whereRaw('(1 - (embedding <=> ?)) > 0.6', [$vectorString]) // Threshold
    //         ->orderByDesc('similarity')
    //         ->limit(5)
    //         ->get();

    //     // 3️⃣ Tampilkan hasil
    //     foreach ($results as $result) {
    //         $this->info("Ayah ID: {$result->ayah_id} | Similarity: " . round($result->similarity, 4));
    //     }

    //     // Display with quality indicators
    //     foreach ($results as $result) {
    //         $quality = match (true) {
    //             $result->similarity >= 0.85 => '✅ EXCELLENT',
    //             $result->similarity >= 0.75 => '👍 GOOD',
    //             $result->similarity >= 0.65 => '⚠️ MODERATE',
    //             default => '❌ WEAK'
    //         };

    //         $this->info("Ayah ID: {$result->ayah_id} | Similarity: " .
    //             round($result->similarity, 4) . " {$quality}");
    //     }

    //     $this->info('✅ Semantic search complete!');
    // }

    public function handle(): int
    {
        $query = $this->argument('query');
        $client = \OpenAI::client(env('OPENAI_API_KEY'));

        $this->info("🔍 Searching semantically for: \"$query\"");

        // 1. Generate embedding untuk query
        $embedding = $client->embeddings()->create([
            'model' => env('OPENAI_EMBED_MODEL', 'text-embedding-3-small'),
            'input' => $query,
        ]);

        $queryVector = $embedding['data'][0]['embedding'];
        $vectorLength = count($queryVector);
        $this->line("Embedding vector length: {$vectorLength}");

        // 2. Siapkan JSON untuk query
        $orderVector = json_encode($queryVector);

        // 3. Query similarity search dengan literal vector
        $results = DB::table('embeddings')
            ->join('ayahs', 'embeddings.ayah_id', '=', 'ayahs.id')
            ->select(
                'ayahs.surah_name',
                'ayahs.ayah_in_surah',
                'ayahs.text_id',
                DB::raw("(embedding <=> '{$orderVector}') AS distance")
            )
            ->orderByRaw("embedding <=> '{$orderVector}'")
            ->limit(5)
            ->get();

        // 4. Tampilkan hasil
        if ($results->isEmpty()) {
            $this->warn("⚠️  Tidak ada hasil ditemukan.");
            return self::SUCCESS;
        }

        $this->info("\n🧠 Top 5 hasil pencarian semantik:\n");

        foreach ($results as $r) {
            $similarity = round(1 - $r->distance, 4); // cosine similarity
            $this->line("📖 {$r->surah_name} [Ayat {$r->ayah_in_surah}] (score: {$similarity})");
            $this->line("   {$r->text_id}\n");
        }

        $this->info("✅ Semantic search complete!");
        return self::SUCCESS;
    }
}
