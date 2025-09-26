<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Transfer Receipt</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif; background: #0b1020; color: #e6e8ec; margin: 0; }
        .container { max-width: 720px; margin: 40px auto; padding: 24px; background: #121836; border-radius: 14px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        h1 { margin: 0 0 8px; font-size: 22px; }
        p.muted { color: #9aa3b2; margin-top: 4px; }
        .card { background: #0e1430; border: 1px solid #1c2347; border-radius: 12px; padding: 16px; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .kv { display: flex; justify-content: space-between; margin: 6px 0; color: #c9d4e5; }
        .kv strong { color: #e6e8ec; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 12px; font-weight: 600; }
        .badge-info { background: #2b4b7b; color: #cbe1ff; }
        .badge-success { background: #2b7b4a; color: #caffdd; }
        .badge-warning { background: #715d2a; color: #ffe7a3; }
        .badge-danger { background: #6e2a3c; color: #ffd6e7; }
        .timeline { margin-top: 12px; }
        .timeline-item { padding: 8px 0; border-bottom: 1px dashed #1c2347; }
        .timeline-item:last-child { border-bottom: 0; }
        .btn { background: #f59e0b; color: #1a1b2e; padding: 10px 12px; border-radius: 10px; font-weight: 600; border: none; cursor: pointer; }
        .btn:hover { background: #f7b23a; }
        .actions { margin-top: 16px; display: flex; gap: 12px; }
    </style>
</head>
<body>
<div class="container">
    <h1>Transfer Receipt</h1>
    <p class="muted">Reference: {{ $transfer->id }} • Status: <span class="badge {{ $transfer->status === 'failed' ? 'badge-danger' : ($transfer->status === 'payout_success' ? 'badge-success' : 'badge-info') }}">{{ $transfer->status }}</span></p>

    @if(session('error'))
        <div class="badge badge-danger" style="display:inline-block;margin-bottom:10px;">{{ session('error') }}</div>
    @endif
    @if(session('info'))
        <div class="badge badge-info" style="display:inline-block;margin-bottom:10px;">{{ session('info') }}</div>
    @endif

    <div class="card">
        <div class="grid-2">
            <div>
                <div class="kv"><span>You pay (XAF)</span><strong>{{ number_format($transfer->total_pay_xaf) }}</strong></div>
                <div class="kv"><span>Fees (XAF)</span><strong>{{ number_format($transfer->fee_total_xaf) }}</strong></div>
                <div class="kv"><span>FX (XAF→NGN)</span><strong>{{ number_format($transfer->adjusted_rate_xaf_to_ngn, 6) }}</strong></div>
                <div class="kv"><span>Recipient gets (NGN)</span><strong>{{ number_format($transfer->receive_ngn_minor / 100, 2) }}</strong></div>
            </div>
            <div>
                <div class="kv"><span>Recipient</span><strong>{{ $transfer->recipient_account_name }}</strong></div>
                <div class="kv"><span>Bank</span><strong>{{ $transfer->recipient_bank_name }}</strong></div>
                <div class="kv"><span>Account</span><strong>{{ substr($transfer->recipient_account_number, -4) ? '••••' . substr($transfer->recipient_account_number, -4) : '' }}</strong></div>
                <div class="kv"><span>Created</span><strong>{{ $transfer->created_at->toDayDateTimeString() }}</strong></div>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:14px;">
        <h3 style="margin-top:0;">Status Timeline</h3>
        <div class="timeline">
            @if(is_array($transfer->timeline))
                @foreach($transfer->timeline as $item)
                    <div class="timeline-item">
                        <strong>{{ $item['state'] ?? 'state' }}</strong> • <span class="muted">{{ $item['at'] ?? '' }}</span>
                    </div>
                @endforeach
            @else
                <div class="timeline-item">No timeline available.</div>
            @endif
        </div>
    </div>

    <div class="actions">
        <a class="btn" href="{{ route('transfer.bank') }}">New Transfer</a>
        <form method="post" action="{{ route('transfer.payout', $transfer) }}" style="display:inline-block;">
            @csrf
            <button class="btn" type="submit">Initiate NGN Payout</button>
        </form>
        <form method="post" action="{{ route('transfer.payout.status', $transfer) }}" style="display:inline-block;">
            @csrf
            <button class="btn" type="submit">Check Payout Status</button>
        </form>
    </div>
</div>
</body>
</html>
