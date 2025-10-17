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

    public function handle()
    {
        $query = $this->argument('query');
        $apiKey = env('OPENAI_API_KEY');

        // 1️⃣ Buat embedding dari pertanyaan
        $response = Http::withToken($apiKey)
            ->post('https://api.openai.com/v1/embeddings', [
                'model' => 'text-embedding-3-small',
                'input' => $query,
            ]);

        if ($response->failed()) {
            $this->error("Failed to generate embedding for query");
            $this->line($response->body());
            return;
        }

        $queryVector = $response->json('data.0.embedding');
        $vectorString = '[' . implode(',', $queryVector) . ']';

        // 2️⃣ Cari embedding terdekat di database
        $results = DB::table('embeddings')
            ->select('id', 'ayah_id')
            ->selectRaw('(1 - (embedding <=> ?)) as similarity', [$vectorString])
            ->whereRaw('(1 - (embedding <=> ?)) > 0.6', [$vectorString]) // Threshold
            ->orderByDesc('similarity')
            ->limit(5)
            ->get();

        // 3️⃣ Tampilkan hasil
        foreach ($results as $result) {
            $this->info("Ayah ID: {$result->ayah_id} | Similarity: " . round($result->similarity, 4));
        }

        // Display with quality indicators
        foreach ($results as $result) {
            $quality = match (true) {
                $result->similarity >= 0.85 => '✅ EXCELLENT',
                $result->similarity >= 0.75 => '👍 GOOD',
                $result->similarity >= 0.65 => '⚠️ MODERATE',
                default => '❌ WEAK'
            };

            $this->info("Ayah ID: {$result->ayah_id} | Similarity: " .
                round($result->similarity, 4) . " {$quality}");
        }

        $this->info('✅ Semantic search complete!');
    }
}
