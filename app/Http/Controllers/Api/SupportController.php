<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    public function help(Request $request)
    {
        // Static help topics; can be moved to DB later
        return response()->json([
            'topics' => [
                ['id' => 'getting-started', 'title' => 'Getting Started', 'url' => url('/support/help#getting-started')],
                ['id' => 'kyc', 'title' => 'KYC Verification', 'url' => url('/support/help#kyc')],
                ['id' => 'transfers', 'title' => 'Transfers', 'url' => url('/support/help#transfers')],
                ['id' => 'fees', 'title' => 'Fees & Rates', 'url' => url('/support/help#fees')],
                ['id' => 'security', 'title' => 'Security & PIN', 'url' => url('/support/help#security')],
            ],
        ]);
    }
}
