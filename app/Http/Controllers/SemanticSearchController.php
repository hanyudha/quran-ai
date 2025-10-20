<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenAI\Client;
use Exception;

class SemanticSearchController extends Controller
{
    public function search(Request $request)
    {
        $query = trim($request->input('query'));

        if (!$query) {
            return back()->with('error', 'Masukkan teks pencarian.');
        }

        try {
            // 1️⃣ Buat client OpenAI
            /** @var \OpenAI\Client $client */
            $client = app(Client::class);

            // 2️⃣ Panggil API embeddings
            $response = $client->embeddings()->create([
                'model' => 'text-embedding-3-small',
                'input' => $query,
            ]);

            // 3️⃣ Konversi ke array untuk menghindari warning IDE
            $responseArray = $response->toArray();

            // 4️⃣ Ambil vektor embedding secara aman
            $embedding = data_get($responseArray, 'data.0.embedding');

            if (!$embedding) {
                throw new Exception('Embedding tidak ditemukan dalam response OpenAI.');
            }

            // 5️⃣ Query PostgreSQL (pgvector)
            $embeddingSql = '[' . implode(',', $embedding) . ']';
            $results = DB::select("
                SELECT 
                    ayahs.id, 
                    ayahs.text_ar AS arabic, 
                    ayahs.text_id AS translation,
                    1 - (embeddings.embedding <=> '$embeddingSql') AS similarity
                FROM embeddings
                JOIN ayahs ON ayahs.id = embeddings.ayah_id
                ORDER BY embeddings.embedding <=> '$embeddingSql'
                LIMIT 5
            ");

            // 6️⃣ Kirim ke view
            return view('semantic-results', [
                'query' => $query,
                'results' => $results,
            ]);

        } catch (Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}
