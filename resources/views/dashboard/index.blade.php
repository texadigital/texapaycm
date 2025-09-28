<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Dashboard • TexaPay</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body{font-family:Inter,system-ui,Arial,sans-serif;background:#0b1220;color:#e6e8ec;margin:0}
    header{display:flex;justify-content:space-between;align-items:center;padding:16px 20px;background:#0f172a;border-bottom:1px solid #1f2a44}
    .container{max-width:1000px;margin:0 auto;padding:20px}
    .btn{display:inline-block;background:#2563eb;border:none;color:#fff;padding:10px 14px;border-radius:8px;font-weight:600;cursor:pointer;text-decoration:none}
    .btn-danger{background:#dc2626}
    table{width:100%;border-collapse:collapse;margin-top:16px}
    th,td{padding:10px;border-bottom:1px solid #1f2a44;text-align:left}
    .badge{padding:3px 8px;border-radius:999px;font-size:12px;font-weight:600}
    .b-success{background:#064e3b;color:#86efac}
    .b-warn{background:#2f2a12;color:#fde68a}
    .b-fail{background:#3a121a;color:#fecaca}
    .muted{color:#9aa4b2}
  </style>
</head>
<body>
  <header>
    <div>Hi, <strong>{{ $user->name }}</strong></div>
    <div style="display:flex;gap:8px;align-items:center;">
      <a href="{{ route('profile.index') }}" class="btn" style="background:#6b7280;">Profile</a>
      <form method="post" action="{{ route('logout') }}" style="margin:0">
        @csrf
        <button class="btn" type="submit">Logout</button>
      </form>
    </div>
  </header>
  <div class="container">
    
    <!-- Limit Warnings -->
    @if(!empty($limitWarnings))
      @foreach($limitWarnings as $warning)
        <div style="background:{{ $warning['level'] === 'critical' ? '#dc2626' : '#f59e0b' }};color:#fff;padding:12px;border-radius:8px;margin-bottom:16px;">
          <strong>{{ $warning['level'] === 'critical' ? '⚠️ Critical' : '⚠️ Warning' }}:</strong> {{ $warning['message'] }}
        </div>
      @endforeach
    @endif

    <!-- Transaction Limits Overview -->
    @if(isset($limitStatus['limits']))
    <div style="background:#1e293b;border-radius:12px;padding:20px;margin-bottom:24px;border:1px solid #334155;">
      <h3 style="margin:0 0 16px 0;color:#f8fafc;">📊 Your Transaction Limits</h3>
      
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px;">
        <!-- Daily Limits -->
        <div style="background:#0f172a;padding:16px;border-radius:8px;border:1px solid #1e293b;">
          <h4 style="margin:0 0 12px 0;color:#60a5fa;">Daily Limits</h4>
          
          <!-- Daily Amount -->
          <div style="margin-bottom:12px;">
            <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
              <span style="color:#cbd5e1;font-size:14px;">Amount</span>
              <span style="color:#f8fafc;font-size:14px;">{{ number_format($limitStatus['usage']['daily_amount']) }} / {{ number_format($limitStatus['limits']['daily_limit_xaf']) }} XAF</span>
            </div>
            <div style="background:#374151;height:8px;border-radius:4px;overflow:hidden;">
              <div style="background:{{ $limitStatus['utilization']['daily_percentage'] >= 80 ? '#ef4444' : '#10b981' }};height:100%;width:{{ min(100, $limitStatus['utilization']['daily_percentage']) }}%;transition:width 0.3s ease;"></div>
            </div>
            <div style="color:#9ca3af;font-size:12px;margin-top:2px;">{{ number_format($limitStatus['utilization']['daily_percentage'], 1) }}% used</div>
          </div>
          
          <!-- Daily Count -->
          <div>
            <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
              <span style="color:#cbd5e1;font-size:14px;">Transactions</span>
              <span style="color:#f8fafc;font-size:14px;">{{ $limitStatus['usage']['daily_count'] }} / {{ $limitStatus['limits']['daily_count_limit'] }}</span>
            </div>
            <div style="background:#374151;height:8px;border-radius:4px;overflow:hidden;">
              <div style="background:{{ $limitStatus['utilization']['daily_count_percentage'] >= 80 ? '#ef4444' : '#10b981' }};height:100%;width:{{ min(100, $limitStatus['utilization']['daily_count_percentage']) }}%;transition:width 0.3s ease;"></div>
            </div>
            <div style="color:#9ca3af;font-size:12px;margin-top:2px;">{{ number_format($limitStatus['utilization']['daily_count_percentage'], 1) }}% used</div>
          </div>
        </div>

        <!-- Monthly Limits -->
        <div style="background:#0f172a;padding:16px;border-radius:8px;border:1px solid #1e293b;">
          <h4 style="margin:0 0 12px 0;color:#34d399;">Monthly Limits</h4>
          
          <!-- Monthly Amount -->
          <div style="margin-bottom:12px;">
            <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
              <span style="color:#cbd5e1;font-size:14px;">Amount</span>
              <span style="color:#f8fafc;font-size:14px;">{{ number_format($limitStatus['usage']['monthly_amount']) }} / {{ number_format($limitStatus['limits']['monthly_limit_xaf']) }} XAF</span>
            </div>
            <div style="background:#374151;height:8px;border-radius:4px;overflow:hidden;">
              <div style="background:{{ $limitStatus['utilization']['monthly_percentage'] >= 80 ? '#ef4444' : '#10b981' }};height:100%;width:{{ min(100, $limitStatus['utilization']['monthly_percentage']) }}%;transition:width 0.3s ease;"></div>
            </div>
            <div style="color:#9ca3af;font-size:12px;margin-top:2px;">{{ number_format($limitStatus['utilization']['monthly_percentage'], 1) }}% used</div>
          </div>
          
          <!-- Monthly Count -->
          <div>
            <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
              <span style="color:#cbd5e1;font-size:14px;">Transactions</span>
              <span style="color:#f8fafc;font-size:14px;">{{ $limitStatus['usage']['monthly_count'] }} / {{ $limitStatus['limits']['monthly_count_limit'] }}</span>
            </div>
            <div style="background:#374151;height:8px;border-radius:4px;overflow:hidden;">
              <div style="background:{{ $limitStatus['utilization']['monthly_count_percentage'] >= 80 ? '#ef4444' : '#10b981' }};height:100%;width:{{ min(100, $limitStatus['utilization']['monthly_count_percentage']) }}%;transition:width 0.3s ease;"></div>
            </div>
            <div style="color:#9ca3af;font-size:12px;margin-top:2px;">{{ number_format($limitStatus['utilization']['monthly_count_percentage'], 1) }}% used</div>
          </div>
        </div>

        <!-- 30-Day Statistics -->
        @if(isset($userStats))
        <div style="background:#0f172a;padding:16px;border-radius:8px;border:1px solid #1e293b;">
          <h4 style="margin:0 0 12px 0;color:#fbbf24;">30-Day Statistics</h4>
          
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:14px;">
            <div>
              <div style="color:#cbd5e1;">Total Sent</div>
              <div style="color:#f8fafc;font-weight:600;">{{ number_format($userStats['successful_amount']) }} XAF</div>
            </div>
            <div>
              <div style="color:#cbd5e1;">Success Rate</div>
              <div style="color:#f8fafc;font-weight:600;">{{ number_format($userStats['success_rate'], 1) }}%</div>
            </div>
            <div>
              <div style="color:#cbd5e1;">Transactions</div>
              <div style="color:#f8fafc;font-weight:600;">{{ $userStats['successful_count'] }}</div>
            </div>
            <div>
              <div style="color:#cbd5e1;">Active Days</div>
              <div style="color:#f8fafc;font-weight:600;">{{ $userStats['active_days'] }}</div>
            </div>
          </div>
        </div>
        @endif
      </div>
    </div>
    @endif

    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px">
      <div>
        <h2 style="margin:0">Your transfers</h2>
        <p class="muted" style="margin:4px 0 0 0">Only your latest 5 transfers are shown here.</p>
      </div>
      <div style="display:flex;gap:8px">
        <a class="btn" href="{{ route('transfer.bank') }}">Send</a>
        <a class="btn" href="{{ route('transactions.index') }}">See more transactions</a>
      </div>
    </div>

    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Pay-in</th>
          <th>Payout</th>
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
            @php $c=$t->payin_status==='success'?'b-success':($t->payin_status==='pending'?'b-warn':'b-fail'); @endphp
            <span class="badge {{ $c }}">{{ $t->payin_status ?? 'n/a' }}</span>
          </td>
          <td>
            @php $c=$t->payout_status==='success'?'b-success':($t->payout_status==='pending'?'b-warn':'b-fail'); @endphp
            <span class="badge {{ $c }}">{{ $t->payout_status ?? 'n/a' }}</span>
          </td>
          <td>
            @php $map=['quote_created'=>'b-warn','payin_pending'=>'b-warn','payin_success'=>'b-success','payout_pending'=>'b-warn','payout_success'=>'b-success','failed'=>'b-fail']; $c=$map[$t->status]??'b-warn'; @endphp
            <span class="badge {{ $c }}">{{ $t->status }}</span>
          </td>
          <td>{{ number_format($t->amount_xaf) }}</td>
          <td>{{ $t->recipient_bank_name }}</td>
          <td>{{ $t->recipient_account_number ? str_repeat('•', max(strlen($t->recipient_account_number)-4,0)).substr($t->recipient_account_number,-4) : '' }}</td>
          <td>{{ $t->created_at->diffForHumans() }}</td>
          <td>
            <a class="btn" href="{{ route('transfer.receipt', $t) }}">Open</a>
            <form method="post" action="{{ route('transfer.payout.status', $t) }}" style="display:inline-block;margin-left:6px">
              @csrf
              <button class="btn" type="submit">Refresh</button>
            </form>
          </td>
        </tr>
        @empty
        <tr><td colspan="9" class="muted">No transfers yet.</td></tr>
        @endforelse
      </tbody>
    </table>

    

    <form method="post" action="{{ route('account.delete') }}" onsubmit="return confirm('Delete your account? This cannot be undone.');" style="margin-top:24px">
      @csrf
      <label for="password">Confirm password to delete account</label>
      <input id="password" name="password" type="password" required />
      <button class="btn btn-danger" type="submit">Delete account</button>
    </form>
  </div>
</body>
</html>
