<?php

use App\Http\Controllers\APIController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\ChatController;

Route::get('/', function () {
    return view('openai');
});

// Sending results
Route::get('/openai/form', [APIController::class, 'showForm'])->name('openai.form');
// Accepting userinput
Route::post('/openai/request', [APIController::class, 'makeOpenAIRequest'])->name('openai.request');

// Clear the session
Route::get('/clear-session', function () {
    Session::forget('conversation');
    return redirect()->route('openai/form'); 
})->name('clear.session');

// Clear the conversation and redirect to the current page
Route::get('/clear-session', [ChatController::class, 'clearSession'])->name('clear.session');