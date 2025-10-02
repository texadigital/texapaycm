<?php

namespace App\Http\Controllers\Webhooks;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Log;

class PawaPayPayoutWebhookController extends BaseController
{
    public function __invoke(Request $request)
    {
        // Log and acknowledge. Extend later to link to internal payouts if needed.
        Log::info('PawaPay payout webhook received', [
            'headers' => $request->headers->all(),
            'payload' => $request->json()->all() ?: $request->all(),
        ]);

        return response()->json(['ok' => true]);
    }
}
