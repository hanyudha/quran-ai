<?php
// database/seeders/TafsirSourceSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TafsirSourceSeeder extends Seeder
{
    public function run(): void
    {
        $sources = 
        [
            [
                'code' => 'kemenag',
                'name' => 'Kementerian Agama Republik Indonesia',
                'author' => 'Kemenag RI',
                'language' => 'id',
                'description' => 'Tafsir resmi Kementerian Agama Republik Indonesia',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'code' => 'quraish',
                'name' => 'Tafsir Al-Misbah',
                'author' => 'Prof. Dr. M. Quraish Shihab',
                'language' => 'id', 
                'description' => 'Tafsir Al-Quran lengkap 30 juz oleh M. Quraish Shihab',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'code' => 'jalalayn',
                'name' => 'Tafsir Jalalayn',
                'author' => 'Jalaluddin al-Mahalli dan Jalaluddin as-Suyuti',
                'language' => 'id',
                'description' => 'Tafsir klasik yang sangat populer di dunia Islam',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];

        foreach ($sources as $source) {
            DB::table('tafsir_sources')->updateOrInsert(
                ['code' => $source['code']],
                $source
            );
        }

        $this->command->info('Tafsir sources seeded successfully!');
    }
}