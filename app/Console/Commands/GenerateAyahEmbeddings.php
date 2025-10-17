<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ayah;
use App\Models\Embedding;
use Illuminate\Support\Facades\Http;

class GenerateAyahEmbeddings extends Command
{
    protected $signature = 'quran:generate-embeddings {--limit=100}';
    protected $description = 'Generate embeddings for ayat from the Quran using OpenAI API';

    public function handle()
    {
        $apiKey = config('services.openai.api_key');
        if (!$apiKey) {
            $this->error('Missing OPENAI_API_KEY in .env');
            return 1;
        }

        $limit = (int) $this->option('limit');
        $ayahs = Ayah::limit($limit)->get();

        foreach ($ayahs as $ayah) {
            $this->info("Processing Ayah ID: {$ayah->id}");

            // Panggil API OpenAI untuk embedding
            $response = Http::withToken($apiKey)
                ->post('https://api.openai.com/v1/embeddings', [
                    'model' => 'text-embedding-3-small',
                    'input' => $ayah->text_id ?? $ayah->text_ar,
                ]);

            if ($response->failed()) {
                $this->error("Failed to get embedding for Ayah ID {$ayah->id}");
                continue;
            }

            $embeddingData = $response->json('data.0.embedding');

            // Simpan ke tabel embeddings
            Embedding::updateOrCreate(
                ['ayah_id' => $ayah->id],
                ['embedding' => $embeddingData]
            );
        }

        $this->info('✅ Embeddings generated successfully!');
        return 0;
    }
}
