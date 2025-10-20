<?php

// app/Console/Commands/SearchQuranCommand.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use OpenAI;

class SearchQuranCommand extends Command
{
    protected $signature = 'quran:search {query} {--limit=5}';
    protected $description = 'Pencarian semantik ayat berdasarkan makna';

    public function handle(): void
    {
        $query = $this->argument('query');
        $limit = (int)$this->option('limit');

        $client = app(\OpenAI\Client::class);
        $response = $client->embeddings()->create([
            'model' => 'text-embedding-3-large',
            'input' => $query,
        ]);

        $queryVector = '[' . implode(',', $response->embeddings[0]->embedding) . ']';

        $results = DB::select("
            SELECT a.surah_id, a.ayah_in_surah, a.text_ar, a.text_id,
                   (e.embedding <=> ?::vector) AS distance
            FROM embeddings e
            JOIN ayahs a ON a.id = e.ayah_id
            ORDER BY distance ASC
            LIMIT ?
        ", [$queryVector, $limit]);

        foreach ($results as $r) {
            $this->line("📖 Surah {$r->surah_id}:{$r->ayah_in_surah} | Distance: " . round($r->distance, 4));
            $this->line($r->text_id);
            $this->line('');
        }
    }
}

