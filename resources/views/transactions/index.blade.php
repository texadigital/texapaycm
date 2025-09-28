<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Transactions • TexaPay</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body{font-family:Inter,system-ui,Arial,sans-serif;background:#0b1220;color:#e6e8ec;margin:0}
    header{display:flex;justify-content:space-between;align-items:center;padding:16px 20px;background:#0f172a;border-bottom:1px solid #1f2a44}
    .container{max-width:1100px;margin:0 auto;padding:20px}
    .btn{display:inline-block;background:#2563eb;border:none;color:#fff;padding:10px 14px;border-radius:8px;font-weight:600;cursor:pointer;text-decoration:none}
    .btn-secondary{background:#374151}
    .filters{display:flex;gap:8px;flex-wrap:wrap;margin:12px 0}
    input,select{background:#0f172a;border:1px solid #1f2a44;color:#e6e8ec;padding:8px 10px;border-radius:8px}
    table{width:100%;border-collapse:collapse;margin-top:12px}
    th,td{padding:10px;border-bottom:1px solid #1f2a44;text-align:left}
    .badge{padding:3px 8px;border-radius:999px;font-size:12px;font-weight:600}
    .b-success{background:#064e3b;color:#86efac}
    .b-warn{background:#2f2a12;color:#fde68a}
    .b-fail{background:#3a121a;color:#fecaca}
    .muted{color:#9aa4b2}
    .footer{margin-top:16px;display:flex;justify-content:space-between;align-items:center;gap:12px}
  </style>
</head>
<body>
  <header>
    <div><strong>Transactions</strong></div>
    <div style="display:flex;gap:8px">
      <a class="btn" href="{{ route('transfer.bank') }}">Send</a>
      <a class="btn btn-secondary" href="{{ route('dashboard') }}">Back to Dashboard</a>
    </div>
  </header>
  <div class="container">

    <form method="get" action="{{ route('transactions.index') }}" class="filters">
      <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search by ID, bank, account, status…" />
      <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" />
      <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" />
      <select name="status">
        <option value="">All statuses</option>
        @php $sel = $filters['status'] ?? ''; @endphp
        <option value="success" {{ $sel==='success'?'selected':'' }}>Success</option>
        <option value="pending" {{ $sel==='pending'?'selected':'' }}>Pending</option>
        <option value="failed" {{ $sel==='failed'?'selected':'' }}>Failed</option>
      </select>
      <button class="btn" type="submit">Search</button>
      <a class="btn btn-secondary" href="{{ route('transactions.export', request()->query()) }}">Export PDF</a>
    </form>

    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Overall</th>
          <th>Amount (XAF)</th>
          <th>Bank</th>
          <th>Acct #</th>
          <th>Created</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse ($transfers as $t)
        <tr>
          <td>{{ $t->id }}</td>
          <td>
            @php $map=['quote_created'=>'b-warn','payin_pending'=>'b-warn','payin_success'=>'b-success','payout_pending'=>'b-warn','payout_success'=>'b-success','failed'=>'b-fail']; $c=$map[$t->status]??'b-warn'; @endphp
            <span class="badge {{ $c }}">{{ $t->status }}</span>
          </td>
          <td>{{ number_format($t->amount_xaf) }}</td>
          <td>{{ $t->recipient_bank_name }}</td>
          <td>{{ $t->recipient_account_number ? str_repeat('•', max(strlen($t->recipient_account_number)-4,0)).substr($t->recipient_account_number,-4) : '' }}</td>
          <td>{{ $t->created_at->diffForHumans() }}</td>
          <td>
            <a class="btn" href="{{ route('transactions.show', $t) }}">View</a>
          </td>
        </tr>
        @empty
        <tr><td colspan="7" class="muted">No transactions found.</td></tr>
        @endforelse
      </tbody>
    </table>

    <div style="margin-top:12px">{{ $transfers->links() }}</div>

    <div class="footer">
      <div class="muted">Total Sent (XAF) across all-time:</div>
      <div style="font-weight:700">{{ number_format($totalSentAllTime) }}</div>
    </div>
    <div class="footer">
      <div class="muted">Total Paid (XAF, incl. fees) across all-time:</div>
      <div style="font-weight:700">{{ number_format($totalPaidAllTime) }}</div>
    </div>

  </div>
</body>
</html>
