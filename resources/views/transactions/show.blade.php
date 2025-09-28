<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Transaction #{{ $t->id }} • TexaPay</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body{font-family:Inter,system-ui,Arial,sans-serif;background:#0b1220;color:#e6e8ec;margin:0}
    header{display:flex;justify-content:space-between;align-items:center;padding:16px 20px;background:#0f172a;border-bottom:1px solid #1f2a44}
    .container{max-width:900px;margin:0 auto;padding:20px}
    .btn{display:inline-block;background:#2563eb;border:none;color:#fff;padding:10px 14px;border-radius:8px;font-weight:600;cursor:pointer;text-decoration:none}
    .grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
    .card{background:#0f172a;border:1px solid #1f2a44;border-radius:12px;padding:16px}
    .label{color:#9aa4b2;font-size:12px}
    .value{font-weight:700}
    .badge{padding:3px 8px;border-radius:999px;font-size:12px;font-weight:600}
    .b-success{background:#064e3b;color:#86efac}
    .b-warn{background:#2f2a12;color:#fde68a}
    .b-fail{background:#3a121a;color:#fecaca}
    .muted{color:#9aa4b2}
    table{width:100%;border-collapse:collapse}
    th,td{padding:8px;border-bottom:1px solid #1f2a44;text-align:left}
    code{background:#0b1220;padding:2px 5px;border-radius:6px;border:1px solid #1f2a44}
  </style>
</head>
<body>
  <header>
    <div><strong>Transaction #{{ $t->id }}</strong></div>
    <div style="display:flex;gap:8px">
      <a class="btn" href="{{ route('transactions.index') }}">Back to Transactions</a>
      <a class="btn" href="{{ route('transfer.receipt', $t) }}">Open Receipt</a>
    </div>
  </header>
  <div class="container">

    <div class="grid">
      <div class="card">
        <div class="label">Amount Sent (XAF)</div>
        <div class="value">{{ number_format($t->amount_xaf) }}</div>
        <div class="label" style="margin-top:8px">Fee (XAF)</div>
        <div class="value">{{ number_format($t->fee_total_xaf) }}</div>
        <div class="label" style="margin-top:8px">Total Paid (XAF)</div>
        <div class="value">{{ number_format($t->total_pay_xaf) }}</div>
      </div>
      <div class="card">
        <div class="label">Adjusted Rate (XAF → NGN)</div>
        <div class="value">{{ number_format($t->adjusted_rate_xaf_to_ngn, 4) }}</div>
        <div class="label" style="margin-top:8px">Receive (NGN)</div>
        <div class="value">{{ number_format($t->receive_ngn) }}</div>
      </div>
    </div>

    <div class="grid" style="margin-top:16px">
      <div class="card">
        <div class="label">Recipient</div>
        <div class="value">{{ $t->recipient_account_name }}</div>
        <div class="muted">{{ $t->recipient_bank_name }} ({{ $t->recipient_bank_code }})</div>
        <div class="muted">
          @php $acct = $t->recipient_account_number; @endphp
          Acct: {{ $acct ? str_repeat('•', max(strlen($acct)-4,0)).substr($acct,-4) : '' }}
        </div>
      </div>
      <div class="card">
        <div class="label">Statuses</div>
        @php $map=['quote_created'=>'b-warn','payin_pending'=>'b-warn','payin_success'=>'b-success','payout_pending'=>'b-warn','payout_success'=>'b-success','failed'=>'b-fail']; $c=$map[$t->status]??'b-warn'; @endphp
        <div>Overall: <span class="badge {{ $c }}">{{ $t->status }}</span></div>
        @php $pi=$t->payin_status==='success'?'b-success':($t->payin_status==='pending'?'b-warn':'b-fail'); @endphp
        <div>Pay-in: <span class="badge {{ $pi }}">{{ $t->payin_status ?? 'n/a' }}</span> @if($t->payin_ref)<span class="muted">ref:</span> <code>{{ $t->payin_ref }}</code>@endif</div>
        @php $po=$t->payout_status==='success'?'b-success':($t->payout_status==='pending'?'b-warn':'b-fail'); @endphp
        <div>Payout: <span class="badge {{ $po }}">{{ $t->payout_status ?? 'n/a' }}</span> @if($t->payout_ref)<span class="muted">ref:</span> <code>{{ $t->payout_ref }}</code>@endif</div>
      </div>
    </div>

    <div class="card" style="margin-top:16px">
      <div class="label">FX Context</div>
      <div>usd_to_xaf: <code>{{ $t->usd_to_xaf }}</code>, usd_to_ngn: <code>{{ $t->usd_to_ngn }}</code>, fetched: <span class="muted">{{ optional($t->fx_fetched_at)->toDateTimeString() }}</span></div>
    </div>

    <div class="card" style="margin-top:16px">
      <div class="label">Timeline</div>
      <table style="margin-top:8px">
        <thead><tr><th>At</th><th>State</th><th>Details</th></tr></thead>
        <tbody>
          @foreach ((array)($t->timeline ?? []) as $e)
          <tr>
            <td class="muted">{{ $e['at'] ?? '' }}</td>
            <td>{{ $e['state'] ?? '' }}</td>
            <td class="muted">{{ json_encode(collect($e)->except(['at','state'])) }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>

  </div>
</body>
</html>
