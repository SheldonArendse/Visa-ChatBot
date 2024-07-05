<?php

use App\Http\Controllers\APIController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/openai/request', [APIController::class, 'makeOpenAIRequest']);