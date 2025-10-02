<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ $notification->title }}</title>
  <style>body{font-family:Arial,sans-serif;color:#333;margin:0}.wrap{max-width:600px;margin:0 auto}.header{background:#dc3545;color:#fff;padding:20px;text-align:center}.content{background:#f8f9fa;padding:24px}.card{background:#fff;border-left:4px solid #dc3545;padding:16px;border-radius:4px}.label{color:#6c757d;font-size:12px;text-transform:uppercase;display:block}.footer{color:#6c757d;font-size:12px;padding:16px;text-align:center}.cta{display:inline-block;background:#0b5ed7;color:#fff;padding:12px 20px;border-radius:4px;text-decoration:none;margin:16px 0}</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <div>TexaPay</div>
    <div>Transfer Failed</div>
  </div>
  <div class="content">
    @php($t=$notification->payload['transfer']??[])
    <p>Hello {{ $user->name }},</p>
    <p>Your transfer could not be completed. A refund will be initiated automatically if payment was received.</p>
    <div class="card">
      <div><span class="label">Reason</span><strong>{{ $notification->payload['reason'] ?? 'Unknown error' }}</strong></div>
      <div><span class="label">Reference</span><strong>{{ $t['payout_ref'] ?? ($t['payin_ref'] ?? ($t['id'] ?? 'N/A')) }}</strong></div>
    </div>
    <a class="cta" href="{{ isset($t['id']) ? route('transfer.receipt',$t['id']) : route('dashboard') }}">View Details</a>
  </div>
  <div class="footer">© {{ date('Y') }} TexaPay.</div>
</div>
</body>
</html>
