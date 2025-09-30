@extends('layouts.app')

@section('content')
  <style>
    .container{max-width:1100px;margin:0 auto;padding:20px}
    .btn{display:inline-block;background:#2563eb;border:none;color:#fff;padding:10px 14px;border-radius:8px;font-weight:600;cursor:pointer;text-decoration:none}
    .btn-secondary{background:#6b7280}
    .filters{display:flex;gap:8px;flex-wrap:wrap;margin:12px 0}
    input,select{background:#ffffff;border:1px solid #e5e7eb;color:#111827;padding:8px 10px;border-radius:8px}
    table{width:100%;border-collapse:collapse;margin-top:12px}
    th,td{padding:10px;border-bottom:1px solid #e5e7eb;text-align:left}
    .badge{padding:3px 8px;border-radius:999px;font-size:12px;font-weight:600}
    .b-success{background:#e6fffa;color:#065f46}
    .b-warn{background:#fff7ed;color:#92400e}
    .b-fail{background:#fef2f2;color:#991b1b}
    .muted{color:#6b7280}
    .footer{margin-top:16px;display:flex;justify-content:space-between;align-items:center;gap:12px}
  </style>
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
      <div class="muted">Total Successfully Sent (XAF):</div>
      <div style="font-weight:700">{{ number_format($totalSentAllTime) }}</div>
    </div>
    <div class="footer">
      <div class="muted">Total Successfully Paid (XAF, incl. fees):</div>
      <div style="font-weight:700">{{ number_format($totalPaidAllTime) }}</div>
    </div>

  </div>
@endsection
