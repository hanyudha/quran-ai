<?php

namespace App\Console\Commands;

use App\Services\Quran\Chat\QuranChatService;
use Illuminate\Console\Command;

class TestQuranBasic extends Command
{
    protected $signature = 'test:quran-basic {message}';
    protected $description = 'Basic Quran Chat test without embeddings';

    public function handle(QuranChatService $quranChatService)
    {
        $message = $this->argument('message');
        
        $this->info("Testing Quran Chat with: {$message}");
        
        try {
            $result = $quranChatService->processMessage($message);
            
            if ($result['success']) {
                $this->info("✅ SUCCESS!");
                $this->line("Answer: {$result['answer']}");
                
                if (!empty($result['similar_verses'])) {
                    $this->line("\nVerses used:");
                    foreach ($result['similar_verses'] as $verse) {
                        $this->line("- {$verse['surah_name']} {$verse['verse_number']} (Similarity: {$verse['similarity']})");
                    }
                }
            } else {
                $this->error("❌ FAILED: {$result['error']}");
            }
            
        } catch (\Exception $e) {
            $this->error("💥 Exception: " . $e->getMessage());
        }
    }
}