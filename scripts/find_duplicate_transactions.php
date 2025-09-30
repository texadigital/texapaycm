<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\Transfer;
use App\Models\User;
use Carbon\Carbon;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== DUPLICATE TRANSACTION INVESTIGATION ===\n\n";

// Find recent transfers (last 24 hours)
$recentTransfers = Transfer::with('user')
    ->where('created_at', '>=', Carbon::now()->subDay())
    ->orderBy('created_at', 'desc')
    ->get();

echo "Recent transfers (last 24 hours): " . $recentTransfers->count() . "\n\n";

// Group by user and amount to find potential duplicates
$potentialDuplicates = $recentTransfers->groupBy(function($transfer) {
    return $transfer->user_id . '_' . $transfer->amount_xaf . '_' . $transfer->recipient_account_number;
})->filter(function($group) {
    return $group->count() > 1;
});

if ($potentialDuplicates->count() > 0) {
    echo "POTENTIAL DUPLICATE TRANSACTIONS FOUND:\n";
    echo "========================================\n\n";
    
    foreach ($potentialDuplicates as $key => $transfers) {
        $user = $transfers->first()->user;
        $amount = $transfers->first()->amount_xaf;
        $account = $transfers->first()->recipient_account_number;
        
        echo "User: " . ($user->name ?? 'Unknown') . " (ID: " . $user->id . ")\n";
        echo "Amount: " . number_format($amount) . " XAF\n";
        echo "Account: " . $account . "\n";
        echo "Duplicates: " . $transfers->count() . "\n";
        echo "Transfer IDs: " . $transfers->pluck('id')->implode(', ') . "\n";
        echo "Created at: " . $transfers->pluck('created_at')->implode(', ') . "\n";
        echo "Statuses: " . $transfers->pluck('status')->implode(', ') . "\n";
        echo "Payin Refs: " . $transfers->pluck('payin_ref')->filter()->implode(', ') . "\n";
        echo "Payout Refs: " . $transfers->pluck('payout_ref')->filter()->implode(', ') . "\n";
        echo "---\n\n";
    }
} else {
    echo "No obvious duplicate transactions found in the last 24 hours.\n\n";
}

// Show all recent transfers for manual review
echo "ALL RECENT TRANSFERS:\n";
echo "=====================\n\n";

foreach ($recentTransfers as $transfer) {
    $user = $transfer->user;
    echo "ID: " . $transfer->id . "\n";
    echo "User: " . ($user->name ?? 'Unknown') . " (ID: " . $user->id . ")\n";
    echo "Amount: " . number_format($transfer->amount_xaf) . " XAF\n";
    echo "Status: " . $transfer->status . "\n";
    echo "Created: " . $transfer->created_at . "\n";
    echo "Payin Ref: " . ($transfer->payin_ref ?? 'N/A') . "\n";
    echo "Payout Ref: " . ($transfer->payout_ref ?? 'N/A') . "\n";
    echo "Account: " . $transfer->recipient_account_number . "\n";
    echo "---\n\n";
}

echo "Investigation complete.\n";
