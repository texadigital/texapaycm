<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <style>
    body{font-family:DejaVu Sans, Arial, sans-serif;font-size:12px;color:#111}
    h1{font-size:18px;margin:0 0 8px 0}
    .muted{color:#555}
    table{width:100%;border-collapse:collapse}
    th,td{border:1px solid #ccc;padding:6px;text-align:left}
    th{background:#f3f3f3}
  </style>
</head>
<body>
  <h1>TexaPay Transactions</h1>
  <div class="muted">Generated: {{ $generatedAt->format('Y-m-d H:i') }}</div>
  @if($filters['q'] || $filters['from'] || $filters['to'] || $filters['status'])
    <p class="muted">
      Filters:
      @if($filters['q']) q="{{ $filters['q'] }}" @endif
      @if($filters['from']) from={{ $filters['from'] }} @endif
      @if($filters['to']) to={{ $filters['to'] }} @endif
      @if($filters['status']) status={{ $filters['status'] }} @endif
    </p>
  @endif

  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Status</th>
        <th>Amount (XAF)</th>
        <th>Bank</th>
        <th>Account</th>
        <th>Created</th>
      </tr>
    </thead>
    <tbody>
      @foreach($items as $t)
      <tr>
        <td>{{ $t->id }}</td>
        <td>{{ $t->status }}</td>
        <td>{{ number_format($t->amount_xaf) }}</td>
        <td>{{ $t->recipient_bank_name }}</td>
        <td>
          @php $acct=$t->recipient_account_number; @endphp
          {{ $acct ? str_repeat('•', max(strlen($acct)-4,0)).substr($acct,-4) : '' }}
        </td>
        <td>{{ optional($t->created_at)->format('Y-m-d H:i') }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>
</body>
</html>
