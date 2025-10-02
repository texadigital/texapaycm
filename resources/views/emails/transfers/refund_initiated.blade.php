<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ $notification->title }}</title>
  <style>body{font-family:Arial,sans-serif;color:#333;margin:0}.wrap{max-width:600px;margin:0 auto}.header{background:#0b5ed7;color:#fff;padding:20px;text-align:center}.content{background:#f8f9fa;padding:24px}.card{background:#fff;border-left:4px solid #0b5ed7;padding:16px;border-radius:4px}.label{color:#6c757d;font-size:12px;text-transform:uppercase;display:block}.footer{color:#6c757d;font-size:12px;padding:16px;text-align:center}.cta{display:inline-block;background:#0b5ed7;color:#fff;padding:12px 20px;border-radius:4px;text-decoration:none;margin:16px 0}</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <div>TexaPay</div>
    <div>Refund Initiated</div>
  </div>
  <div class="content">
    @php($t=$notification->payload['transfer']??[])
    <p>Hello {{ $user->name }},</p>
    <p>We've initiated a refund for your failed transfer. You will receive your money back shortly.</p>
    <div class="card">
      <div><span class="label">Refund ID</span><strong>{{ $notification->payload['refund_id'] ?? 'N/A' }}</strong></div>
      <div><span class="label">Original Reference</span><strong>{{ $t['payin_ref'] ?? ($t['id'] ?? 'N/A') }}</strong></div>
    </div>
    <a class="cta" href="{{ isset($t['id']) ? route('transfer.receipt',$t['id']) : route('dashboard') }}">View Receipt</a>
  </div>
  <div class="footer">© {{ date('Y') }} TexaPay.</div>
</div>
</body>
</html>
