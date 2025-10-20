<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/semantic-search', [App\Http\Controllers\SemanticSearchController::class, 'search'])
    ->name('semantic.search');
