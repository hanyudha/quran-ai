<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenAI;
use Illuminate\Support\Facades\Log;

class QuranSearchController extends Controller
{
    public function semantic(Request $request)
    {
        // Increase execution time for this request
        set_time_limit(120); // MODIFIKASI: Meningkatkan waktu eksekusi dari 60 menjadi 120 detik
        $query = trim($request->input('q'));
        
        // MODIFIKASI: Validasi query kosong
        if (empty($query)) {
            return response()->json([
                'error' => 'Query tidak boleh kosong'
            ], 400);
        }
        
        $limit = min(max((int) $request->input('limit', 5), 1), 50);
        $tafsirSourceId = (int) $request->input('tafsir_source_id', 1);

        // MODIFIKASI: Menambahkan pattern matching untuk lebih banyak surah
        $surahPatterns = [
            'alfatihah|al-fatihah|fatihah' => 1,
            'albaqarah|al-baqarah|baqarah' => 2,
            'ali imran|ali-imran|imran' => 3,
            // MODIFIKASI: Bisa ditambahkan lebih banyak surah di sini
        ];

        $matchedSurah = null;
        $ayatNumber = null;

        foreach ($surahPatterns as $pattern => $surahId) {
            if (preg_match('/(' . $pattern . ')\s+ayat\s+(\d+)/i', $query, $matches)) {
                $matchedSurah = $surahId;
                $ayatNumber = (int) $matches[2];
                break;
            }
        }

        // MODIFIKASI: Jika ditemukan pattern surah+ayat, lakukan exact match
        if ($matchedSurah && $ayatNumber) {
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
            WHERE a.surah_id = ? AND a.ayah_in_surah = ?
            LIMIT ?
        ", [$tafsirSourceId, $matchedSurah, $ayatNumber, $limit]);

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

        // MODIFIKASI: Menambahkan fallback ke keyword search jika semantic search gagal
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
            // MODIFIKASI: Improved error handling dengan logging dan fallback
            Log::error('Semantic search failed: ' . $e->getMessage(), [
                'query' => $query,
                'tafsir_source_id' => $tafsirSourceId
            ]);

            // Fallback ke keyword search
            return $this->keywordSearch($query, $limit, $tafsirSourceId);
        }
    }

    // MODIFIKASI: Menambahkan method keyword search sebagai fallback
    private function keywordSearch($query, $limit, $tafsirSourceId)
    {
        try {
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
                -- MODIFIKASI: Menambahkan similarity sederhana berdasarkan keyword matching
                CASE 
                    WHEN a.text_id ILIKE ? THEN 0.9
                    WHEN a.text_id ILIKE ? THEN 0.7
                    ELSE 0.5
                END as similarity,
                0.5 as distance
            FROM ayahs a
            LEFT JOIN tafsirs t ON t.ayah_id = a.id AND t.source_id = ?
            WHERE a.text_id ILIKE ? OR a.text_ar ILIKE ?
            ORDER BY similarity DESC
            LIMIT ?
        ", [
            '%' . $query . '%',
            '%' . explode(' ', $query)[0] . '%', // MODIFIKASI: Mencocokkan kata pertama saja
            $tafsirSourceId,
            '%' . $query . '%',
            '%' . $query . '%',
            $limit
        ]);

            $data = collect($results)->map(function ($r) {
                return $this->formatAyahData($r);
            });

            return response()->json([
                'query' => $query,
                'search_type' => 'keyword_fallback',
                'tafsir_source_id' => $tafsirSourceId,
                'results_count' => count($data),
                'results' => $data,
                'note' => 'Semantic search gagal, menggunakan keyword search sebagai fallback'
            ]);
        } catch (\Throwable $e) {
            Log::error('Keyword search also failed: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Pencarian gagal: ' . $e->getMessage(),
                'query' => $query,
                'search_type' => 'failed'
            ], 500);
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

        // MODIFIKASI: Menambahkan sanitasi untuk teks tafsir
        if ($r->tafsir_short || $r->tafsir_long) {
            $baseData['tafsir'] = [
                'source_id' => $r->tafsir_source_id,
                'short_text' => $this->sanitizeTafsirText($r->tafsir_short),
                'long_text' => $this->sanitizeTafsirText($r->tafsir_long),
                'language' => $r->tafsir_language
            ];
        } else {
            $baseData['tafsir'] = null;
        }

        return $baseData;
    }

    // MODIFIKASI: Menambahkan method untuk sanitasi teks tafsir
    private function sanitizeTafsirText($text)
    {
        if (empty($text)) {
            return $text;
        }

        // MODIFIKASI: Membersihkan teks dari karakter yang tidak diinginkan
        $text = trim($text);
        $text = htmlspecialchars_decode($text, ENT_QUOTES | ENT_HTML5);
        
        // MODIFIKASI: Menghapus multiple spaces
        $text = preg_replace('/\s+/', ' ', $text);
        
        return $text;
    }

    /**
     * Method untuk mendapatkan daftar sumber tafsir yang tersedia
     */
    public function availableTafsirSources()
    {
        try {
            // MODIFIKASI: Menambahkan informasi tambahan tentang sumber tafsir
            $sources = DB::table('tafsirs')
                ->select('source_id', 
                         DB::raw('COUNT(*) as ayah_count'),
                         DB::raw('COUNT(DISTINCT ayah_id) as unique_ayah_count'),
                         'language')
                ->groupBy('source_id', 'language')
                ->orderBy('source_id')
                ->get();

            return response()->json([
                'available_tafsir_sources' => $sources,
                'total_sources' => $sources->count()
            ]);
        } catch (\Throwable $e) {
            // MODIFIKASI: Menambahkan logging untuk error
            Log::error('Failed to get tafsir sources: ' . $e->getMessage());
            
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
            // MODIFIKASI: Query yang lebih informatif
            $sampleSources = DB::table('tafsirs')
                ->select('source_id', 'language', DB::raw('COUNT(*) as total_tafsirs'))
                ->groupBy('source_id', 'language')
                ->orderBy('source_id')
                ->limit(20) // MODIFIKASI: Meningkatkan limit
                ->get();

            // MODIFIKASI: Statistik yang lebih detail
            $sourceCounts = DB::table('tafsirs')
                ->select('source_id', 
                         DB::raw('COUNT(DISTINCT ayah_id) as unique_ayah_count'),
                         DB::raw('COUNT(*) as total_tafsir_entries'),
                         DB::raw('MIN(created_at) as first_entry'),
                         DB::raw('MAX(created_at) as last_entry'))
                ->groupBy('source_id')
                ->get();

            // MODIFIKASI: Menambahkan informasi languages per source
            $languagesPerSource = DB::table('tafsirs')
                ->select('source_id', 'language')
                ->distinct()
                ->orderBy('source_id')
                ->get()
                ->groupBy('source_id');

            return response()->json([
                'sample_sources' => $sampleSources,
                'source_statistics' => $sourceCounts,
                'languages_per_source' => $languagesPerSource,
                'summary' => [
                    'total_sources' => $sampleSources->count(),
                    'total_languages' => $languagesPerSource->flatten()->unique()->count()
                ]
            ]);
        } catch (\Throwable $e) {
            // MODIFIKASI: Menambahkan logging untuk error
            Log::error('Failed to get tafsir source info: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Gagal mendapatkan info sumber tafsir: ' . $e->getMessage()
            ], 500);
        }
    }

    // MODIFIKASI: Menambahkan method baru untuk health check
    public function healthCheck()
    {
        try {
            $ayahCount = DB::table('ayahs')->count();
            $tafsirCount = DB::table('tafsirs')->count();
            $embeddingCount = DB::table('embeddings')->count();

            return response()->json([
                'status' => 'healthy',
                'database' => [
                    'total_ayahs' => $ayahCount,
                    'total_tafsirs' => $tafsirCount,
                    'total_embeddings' => $embeddingCount
                ],
                'timestamp' => now()->toISOString()
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ], 500);
        }
    }
}