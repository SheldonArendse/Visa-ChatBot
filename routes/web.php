<?php

use App\Http\Controllers\APIController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('openai');
});

// Sending results
Route::get('/openai', [APIController::class, 'showForm']);
// Accepting userinput
Route::post('/openai/request', [APIController::class, 'makeOpenAIRequest']);
