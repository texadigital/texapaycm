<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ $notification->title }}</title>
    <style>body{font-family:Arial,sans-serif;line-height:1.6;color:#333;margin:0}.wrap{max-width:600px;margin:0 auto}.header{background:#198754;color:#fff;padding:20px;text-align:center}.amount{font-size:22px;font-weight:bold}.content{background:#f8f9fa;padding:24px}.card{background:#fff;border-left:4px solid #198754;padding:16px;border-radius:4px}.label{color:#6c757d;font-size:12px;text-transform:uppercase;display:block}.cta{display:inline-block;background:#198754;color:#fff;padding:12px 20px;border-radius:4px;text-decoration:none;margin:16px 0}.footer{color:#6c757d;font-size:12px;padding:16px;text-align:center}</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <div>TexaPay</div>
    <div class="amount">Payment Received</div>
    <div>{{ $notification->title }}</div>
  </div>
  <div class="content">
    @php($t=$notification->payload['transfer']??[])
    @php($id=$t['id']??null)
    <p>Hello {{ $user->name }},</p>
    <p>Your payment was received successfully. We are processing your payout to the recipient.</p>
    <div class="card">
      <div><span class="label">You Paid (XAF)</span><strong>{{ isset($t['total_pay_xaf'])?number_format($t['total_pay_xaf']):'—' }}</strong></div>
      <div><span class="label">Recipient Gets (NGN)</span><strong>@php($ngn=$t['receive_ngn_minor']??null) {{ $ngn?number_format($ngn/100,2):'—' }}</strong></div>
      <div><span class="label">Reference</span><strong>{{ $t['payin_ref'] ?? $id }}</strong></div>
    </div>
    <a class="cta" href="{{ $id ? route('transfer.receipt',$id) : route('dashboard') }}">View Receipt</a>
  </div>
  <div class="footer">© {{ date('Y') }} TexaPay.</div>
</div>
</body>
</html>
