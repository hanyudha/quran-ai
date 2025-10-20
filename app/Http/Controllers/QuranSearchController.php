<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenAI;

class QuranSearchController extends Controller
{
    public function semantic(Request $request)
    {
        $query = trim($request->input('q'));
        $limit = (int) $request->input('limit', 5);

        if (empty($query)) {
            return response()->json([
                'error' => 'Parameter q (query) wajib diisi.'
            ], 400);
        }

        try {
            // 1️⃣ Buat embedding untuk query
            $client = app(\OpenAI\Client::class);
            $response = $client->embeddings()->create([
                'model' => 'text-embedding-3-large',
                'input' => $query,
            ]);

            $queryVector = '[' . implode(',', $response->embeddings[0]->embedding) . ']';

            // 2️⃣ Query ke database berdasarkan similarity (cosine distance)
            $results = DB::select("
                SELECT a.surah_id, a.ayah_in_surah, a.text_ar, a.text_id,
                       (e.embedding <=> ?::vector) AS distance
                FROM embeddings e
                JOIN ayahs a ON a.id = e.ayah_id
                ORDER BY distance ASC
                LIMIT ?
            ", [$queryVector, $limit]);

            // 3️⃣ Format hasil dengan skor similarity (0 = paling mirip)
            $data = collect($results)->map(function ($r) {
                return [
                    'surah_id' => $r->surah_id,
                    'ayah_in_surah' => $r->ayah_in_surah,
                    'text_ar' => $r->text_ar,
                    'text_id' => $r->text_id,
                    'similarity' => round(1 - $r->distance, 4),
                    'distance' => round($r->distance, 4),
                ];
            });

            return response()->json([
                'query' => $query,
                'results' => $data,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }
}
