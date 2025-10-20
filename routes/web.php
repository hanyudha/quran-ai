<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\QuranFrontendController;
use App\Http\Controllers\QuranChatController;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/', function () {
    return view('quran-chat');
});

Route::post('/semantic-search', [App\Http\Controllers\SemanticSearchController::class, 'search'])
    ->name('semantic.search');

Route::get('/quran-ai', [QuranFrontendController::class, 'index']);

Route::post('/chat/quran', [QuranChatController::class, 'chat']);

// API Routes
Route::post('/api/quran-chat/message', [QuranChatController::class, 'sendMessage']);
Route::get('/api/quran-chat/history/{sessionId}', [QuranChatController::class, 'getChatHistory']);
Route::delete('/api/quran-chat/history/{sessionId}', [QuranChatController::class, 'clearHistory']);
