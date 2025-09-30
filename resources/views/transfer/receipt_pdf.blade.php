<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt #{{ $transfer->id }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #111; }
        .container { max-width: 760px; margin: 0 auto; }
        .header { text-align: center; margin-bottom: 16px; }
        .title { font-size: 20px; font-weight: bold; }
        .muted { color: #666; }
        .section { margin: 16px 0; }
        .kv { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #eee; }
        .kv:last-child { border-bottom: 0; }
        .amount { font-size: 16px; font-weight: bold; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 12px; font-size: 11px; }
        .success { background: #dcfce7; color: #166534; }
        .pending { background: #fef3c7; color: #92400e; }
        .failed { background: #fee2e2; color: #991b1b; }
        .timeline { margin-top: 8px; }
        .timeline-item { margin: 6px 0; }
        .small { font-size: 11px; color: #444; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="title">TexaPay Transfer Receipt</div>
        <div class="muted">Reference #{{ $transfer->id }}</div>
    </div>

    <div class="section">
        @php
            $status = strtolower((string) $transfer->status);
            $cls = 'pending';
            $label = 'In Progress';
            if ($status === 'completed' || $status === 'payout_success') { $cls = 'success'; $label = 'Completed'; }
            if (str_contains($status, 'failed') || $status === 'failed') { $cls = 'failed'; $label = 'Failed'; }
        @endphp
        <span class="badge {{ $cls }}">{{ $label }}</span>
        <div class="small">Created at: {{ $transfer->created_at->toIso8601String() }} (UTC)</div>
        @if($transfer->payout_completed_at)
            <div class="small">Completed at: {{ optional($transfer->payout_completed_at)->toIso8601String() }} (UTC)</div>
        @endif
    </div>

    <div class="section">
        <h3>Amounts</h3>
        <div class="kv"><div>You send</div><div class="amount">{{ number_format($transfer->amount_xaf, 2) }} XAF</div></div>
        <div class="kv"><div>Fee</div><div>{{ number_format($transfer->fee_total_xaf, 2) }} XAF</div></div>
        <div class="kv"><div>Total</div><div>{{ number_format($transfer->total_pay_xaf, 2) }} XAF</div></div>
        <div class="kv"><div>Exchange Rate</div><div>1 XAF = {{ number_format($transfer->adjusted_rate_xaf_to_ngn, 6) }} NGN</div></div>
        <div class="kv"><div>Recipient Gets</div><div class="amount">{{ number_format($transfer->receive_ngn_minor / 100, 2) }} NGN</div></div>
    </div>

    <div class="section">
        <h3>Recipient</h3>
        <div class="kv"><div>Name</div><div>{{ $transfer->recipient_account_name }}</div></div>
        <div class="kv"><div>Bank</div><div>{{ $transfer->recipient_bank_name }}</div></div>
        <div class="kv"><div>Account</div><div>{{ substr($transfer->recipient_account_number, 0, 2) . '••••' . substr($transfer->recipient_account_number, -4) }}</div></div>
    </div>

    <div class="section">
        <h3>Timeline</h3>
        <div class="timeline">
            @forelse(($transfer->timeline ?? []) as $ev)
                <div class="timeline-item">
                    <div class="small">{{ \Carbon\Carbon::parse($ev['at'] ?? $transfer->created_at)->toIso8601String() }} (UTC)</div>
                    <div>{{ ucwords(str_replace('_', ' ', (string)($ev['state'] ?? 'event'))) }}</div>
                    @if(!empty($ev['reason']))<div class="small">{{ $ev['reason'] }}</div>@endif
                </div>
            @empty
                <div class="small">No timeline events available.</div>
            @endforelse
        </div>
    </div>

    <div class="section small">
        Generated: {{ now()->toIso8601String() }} (UTC)
    </div>
</div>
</body>
</html>
