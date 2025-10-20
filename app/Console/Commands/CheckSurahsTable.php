<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckSurahsTable extends Command
{
    protected $signature = 'check:surahs';
    protected $description = 'Check surahs table structure';

    public function handle()
    {
        $this->info("Checking surahs table...");
        
        if (Schema::hasTable('surahs')) {
            $columns = Schema::getColumnListing('surahs');
            $this->info("Columns in surahs table: " . implode(', ', $columns));
            
            // Show sample data
            $sample = DB::table('surahs')->first();
            if ($sample) {
                $this->info("\nSample surah data:");
                foreach ($sample as $key => $value) {
                    $this->line("{$key}: " . ($value ?? 'NULL'));
                }
            }
            
            $count = DB::table('surahs')->count();
            $this->info("\nTotal surahs: {$count}");
        } else {
            $this->error("surahs table does not exist");
        }
    }
}