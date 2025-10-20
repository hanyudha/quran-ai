<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\QuranFrontendController;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/semantic-search', [App\Http\Controllers\SemanticSearchController::class, 'search'])
    ->name('semantic.search');

Route::get('/quran-ai', [QuranFrontendController::class, 'index']);
