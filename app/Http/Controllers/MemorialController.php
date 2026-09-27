<?php

namespace App\Http\Controllers;

use App\Models\Character;
use Illuminate\View\View;

class MemorialController extends Controller
{
    public function index(): View
    {
        return view('characters.memorial', ['characters' => Character::with('user')->where('status', 'deceased')->latest('died_at')->paginate(24)]);
    }
}
