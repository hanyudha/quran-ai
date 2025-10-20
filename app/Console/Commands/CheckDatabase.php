<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckDatabase extends Command
{
    protected $signature = 'check:database';
    protected $description = 'Check database tables and structure';

    public function handle()
    {
        $tables = ['ayahs', 'surahs', 'embeddings', 'chat_histories'];
        
        foreach ($tables as $table) {
            $this->info("Table: {$table}");
            
            if (Schema::hasTable($table)) {
                $count = DB::table($table)->count();
                $columns = Schema::getColumnListing($table);
                
                $this->line("  ✅ Exists with {$count} records");
                $this->line("  Columns: " . implode(', ', $columns));
                
                // Show sample data for ayahs
                if ($table === 'ayahs' && $count > 0) {
                    $sample = DB::table('ayahs')
                        ->join('surahs', 'surahs.id', '=', 'ayahs.surah_id')
                        ->select('ayahs.id', 'ayahs.text_id', 'ayahs.text_ar', 'surahs.name')
                        ->first();
                    
                    if ($sample) {
                        $this->line("  Sample: {$sample->name} {$sample->text_id} - " . substr($sample->text_ar, 0, 50) . "...");
                    }
                }
            } else {
                $this->error("  ❌ Table does not exist");
            }
            $this->line("");
        }
    }
}