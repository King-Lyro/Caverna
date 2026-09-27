<?php

namespace App\Http\Controllers;

use App\Models\AdoptionApplication;
use App\Models\AdoptionListing;
use Illuminate\View\View;

class AdminAdoptionController extends Controller
{
    public function index(): View
    {
        return view('admin.adoption.index', [
            'listings' => AdoptionListing::with(['character', 'owner', 'claimant'])->latest()->paginate(15, ['*'], 'listings'),
            'applications' => AdoptionApplication::with(['listing.character', 'applicant'])->where('status', 'pending')->latest()->paginate(15, ['*'], 'applications'),
        ]);
    }
}
