<?php

namespace App\Http\Controllers;

use App\Services\Quran\Chat\QuranChatService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class QuranChatController extends Controller
{
    private QuranChatService $quranChatService;

    public function __construct(QuranChatService $quranChatService)
    {
        $this->quranChatService = $quranChatService;
    }

    public function sendMessage(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'session_id' => 'nullable|string'
        ]);

        $result = $this->quranChatService->processMessage(
            $request->input('message'),
            $request->input('session_id')
        );

        return response()->json($result);
    }

    public function getChatHistory(string $sessionId): JsonResponse
    {
        $history = $this->quranChatService->getChatHistory($sessionId);
        
        return response()->json([
            'session_id' => $sessionId,
            'history' => $history
        ]);
    }

    public function clearHistory(string $sessionId): JsonResponse
    {
        $result = $this->quranChatService->clearChatHistory($sessionId);
        
        return response()->json([
            'success' => $result,
            'message' => $result ? 'Chat history cleared successfully' : 'No history found to clear'
        ]);
    }
}