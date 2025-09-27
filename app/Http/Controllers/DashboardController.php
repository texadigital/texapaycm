<?php

namespace App\Http\Controllers;

use App\Models\Transfer;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $transfers = Transfer::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('dashboard.index', compact('user', 'transfers'));
    }
}
