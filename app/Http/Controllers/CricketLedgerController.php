<?php

namespace App\Http\Controllers;

use App\Models\CricketLedgerEntry;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CricketLedgerController extends Controller
{
    public function index(Request $request): View
    {
        return view('crickets.index', ['entries' => CricketLedgerEntry::where('user_id', $request->user()->id)->latest()->paginate(30)]);
    }
}
