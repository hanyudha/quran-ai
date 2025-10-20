<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\QuranSearchController;
use App\Http\Controllers\QuranChatController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Rute API untuk aplikasi Quran AI.
| Route ini otomatis memiliki prefix "/api" karena diatur oleh RouteServiceProvider.
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// 🔍 Endpoint pencarian semantik Al-Qur’an
Route::get('/search/semantic', [QuranSearchController::class, 'semantic']);

Route::prefix('quran-chat')->group(function () {
    Route::post('/message', [QuranChatController::class, 'sendMessage']);
    Route::get('/history/{sessionId}', [QuranChatController::class, 'getChatHistory']);
    Route::delete('/history/{sessionId}', [QuranChatController::class, 'clearHistory']);
});
