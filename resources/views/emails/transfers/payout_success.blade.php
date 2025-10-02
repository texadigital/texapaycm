<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ $notification->title }}</title>
  <style>body{font-family:Arial,sans-serif;color:#333;margin:0}.wrap{max-width:600px;margin:0 auto}.header{background:#0b5ed7;color:#fff;padding:20px;text-align:center}.amount{font-size:22px;font-weight:bold}.content{background:#f8f9fa;padding:24px}.card{background:#fff;border-left:4px solid #0b5ed7;padding:16px;border-radius:4px}.label{color:#6c757d;font-size:12px;text-transform:uppercase;display:block}.cta{display:inline-block;background:#0b5ed7;color:#fff;padding:12px 20px;border-radius:4px;text-decoration:none;margin:16px 0}.footer{color:#6c757d;font-size:12px;padding:16px;text-align:center}</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <div>TexaPay</div>
    <div class="amount">Transfer Completed</div>
  </div>
  <div class="content">
    @php($t=$notification->payload['transfer']??[])
    @php($id=$t['id']??null)
    @php($ngn=$t['receive_ngn_minor']??null)
    @php($ngnMajor=$ngn?number_format($ngn/100,2):null)
    <p>Hello {{ $user->name }},</p>
    <p>Your transfer was completed successfully. The recipient has received the funds.</p>
    <div class="card">
      <div><span class="label">Amount (NGN)</span><strong>{{ $ngnMajor ?? '—' }}</strong></div>
      <div><span class="label">Recipient</span><strong>{{ $t['recipient_account_name'] ?? 'Recipient' }} • {{ isset($t['recipient_account_number']) ? substr($t['recipient_account_number'],0,3).'•••'.substr($t['recipient_account_number'],-3) : '—' }}</strong></div>
      <div><span class="label">Reference</span><strong>{{ $t['payout_ref'] ?? ($t['payin_ref'] ?? $id) }}</strong></div>
    </div>
    <a class="cta" href="{{ $id ? route('transfer.receipt',$id) : route('dashboard') }}">View Receipt</a>
  </div>
  <div class="footer">© {{ date('Y') }} TexaPay.</div>
</div>
</body>
</html>
