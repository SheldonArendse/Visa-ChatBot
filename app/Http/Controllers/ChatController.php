<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class ChatController extends Controller
{
    public function clearSession()
    {
        Session::forget('conversation');
        return redirect()->route('openai.form');
    }
}