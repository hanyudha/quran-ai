<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenAI;

class QuranSearchController extends Controller
{
    public function semantic(Request $request)
    {
         // Increase execution time for this request
        set_time_limit(60); // 60 seconds
        $query = trim($request->input('q'));
        $limit = min(max((int) $request->input('limit', 5), 1), 50);
        $tafsirSourceId = (int) $request->input('tafsir_source_id', 1);

        // Deteksi jika query mengandung pencarian spesifik surah+ayat
        if (preg_match('/(alfatihah|al-fatihah)\s+ayat\s+(\d+)/i', $query, $matches)) {
            $ayatNumber = (int) $matches[2];

            // Langsung query ke database untuk hasil exact match
            $exactResults = DB::select("
            SELECT 
                a.surah_id, 
                a.ayah_in_surah, 
                a.text_ar, 
                a.text_id,
                t.short_text as tafsir_short,
                t.long_text as tafsir_long,
                t.source_id as tafsir_source_id,
                t.language as tafsir_language,
                1.0 as similarity, 
                0.0 as distance
            FROM ayahs a
            LEFT JOIN tafsirs t ON t.ayah_id = a.id AND t.source_id = ?
            WHERE a.surah_id = 1 AND a.ayah_in_surah = ?
            LIMIT ?
        ", [$tafsirSourceId, $ayatNumber, $limit]);

            if (!empty($exactResults)) {
                $data = collect($exactResults)->map(function ($r) {
                    return $this->formatAyahData($r);
                });

                return response()->json([
                    'query' => $query,
                    'search_type' => 'exact_match',
                    'tafsir_source_id' => $tafsirSourceId,
                    'results_count' => count($data),
                    'results' => $data,
                ]);
            }
        }

        // Jika bukan exact match, gunakan semantic search seperti biasa
        try {
            $client = app(\OpenAI\Client::class);
            $response = $client->embeddings()->create([
                'model' => 'text-embedding-3-large',
                'input' => $query,
            ]);

            $queryVector = '[' . implode(',', $response->embeddings[0]->embedding) . ']';

            $results = DB::select("
            SELECT 
                a.surah_id, 
                a.ayah_in_surah, 
                a.text_ar, 
                a.text_id,
                t.short_text as tafsir_short,
                t.long_text as tafsir_long,
                t.source_id as tafsir_source_id,
                t.language as tafsir_language,
                (e.embedding <=> ?::vector) AS distance
            FROM embeddings e
            JOIN ayahs a ON a.id = e.ayah_id
            LEFT JOIN tafsirs t ON t.ayah_id = a.id AND t.source_id = ?
            ORDER BY distance ASC
            LIMIT ?
        ", [$queryVector, $tafsirSourceId, $limit]);

            $data = collect($results)->map(function ($r) {
                return $this->formatAyahData($r);
            });

            return response()->json([
                'query' => $query,
                'search_type' => 'semantic',
                'tafsir_source_id' => $tafsirSourceId,
                'results_count' => count($data),
                'results' => $data,
            ]);
        } catch (\Throwable $e) {
            // Error handling
        }
    }

    private function formatAyahData($r)
    {
        $baseData = [
            'surah_id' => $r->surah_id,
            'ayah_in_surah' => $r->ayah_in_surah,
            'text_ar' => $r->text_ar,
            'text_id' => $r->text_id,
            'similarity' => round(1 - $r->distance, 4),
            'distance' => round($r->distance, 4),
        ];

        if ($r->tafsir_short || $r->tafsir_long) {
            $baseData['tafsir'] = [
                'source_id' => $r->tafsir_source_id,
                'short_text' => $r->tafsir_short,
                'long_text' => $r->tafsir_long,
                'language' => $r->tafsir_language
            ];
        } else {
            $baseData['tafsir'] = null;
        }

        return $baseData;
    }

    /**
     * Method untuk mendapatkan daftar sumber tafsir yang tersedia
     */
    public function availableTafsirSources()
    {
        try {
            $sources = DB::table('tafsirs')
                ->select('source_id', DB::raw('COUNT(*) as ayah_count'))
                ->groupBy('source_id')
                ->get();

            return response()->json([
                'available_tafsir_sources' => $sources
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Gagal mendapatkan sumber tafsir: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Method untuk mendapatkan detail sumber tafsir
     */
    public function tafsirSourceInfo()
    {
        try {
            // Ambil sample data untuk melihat source_id yang ada
            $sampleSources = DB::table('tafsirs')
                ->select('source_id', 'language')
                ->groupBy('source_id', 'language')
                ->limit(10)
                ->get();

            // Hitung jumlah ayat per source
            $sourceCounts = DB::table('tafsirs')
                ->select('source_id', DB::raw('COUNT(DISTINCT ayah_id) as unique_ayah_count'))
                ->groupBy('source_id')
                ->get();

            return response()->json([
                'sample_sources' => $sampleSources,
                'source_statistics' => $sourceCounts
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Gagal mendapatkan info sumber tafsir: ' . $e->getMessage()
            ], 500);
        }
    }
}
