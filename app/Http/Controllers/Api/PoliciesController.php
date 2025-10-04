<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PoliciesController extends Controller
{
    public function index(Request $request)
    {
        // Static placeholders; swap to AdminSetting or DB if available
        return response()->json([
            'terms' => [
                'url' => url('/policies#terms'),
                'version' => '1.0.0',
            ],
            'privacy' => [
                'url' => url('/policies#privacy'),
                'version' => '1.0.0',
            ],
            'fees' => [
                'url' => url('/policies#fees'),
                'version' => '1.0.0',
            ],
        ]);
    }
}
