<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Ayah;

class QuranSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        // TODO: loop dari file JSON/CSV anda
        // Contoh dummy:
        Ayah::create([
            'surah' => 2,
            'ayah' => 153,
            'text_ar' => 'يَا أَيُّهَا الَّذِينَ آمَنُوا اسْتَعِينُوا بِالصَّبْرِ وَالصَّلَاةِ...',
            'text_id' => 'Wahai orang-orang yang beriman! Mohonlah pertolongan dengan sabar dan salat...'
        ]);
    }
}
