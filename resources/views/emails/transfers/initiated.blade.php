<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $notification->title }}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin:0; padding:0; }
        .wrapper { max-width:600px; margin:0 auto; padding:0; }
        .header { background:#0b5ed7; color:#fff; padding:20px; text-align:center; }
        .amount { font-size:22px; font-weight:bold; margin-top:6px; }
        .content { background:#f8f9fa; padding:24px; }
        .card { background:#fff; border-left:4px solid #0b5ed7; padding:16px; border-radius:4px; }
        .row { margin:8px 0; }
        .label { color:#6c757d; display:block; font-size:12px; text-transform:uppercase; }
        .cta { display:inline-block; background:#0b5ed7; color:#fff; padding:12px 20px; border-radius:4px; text-decoration:none; margin:16px 0; }
        .footer { color:#6c757d; font-size:12px; padding:16px; text-align:center; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <div>TexaPay</div>
        <div class="amount">Transfer Initiated</div>
        <div>{{ $notification->title }}</div>
    </div>
    <div class="content">
        @php($t = $notification->payload['transfer'] ?? [])
        @php($id = $t['id'] ?? null)
        @php($amountXaf = $t['amount_xaf'] ?? null)
        @php($totalPayXaf = $t['total_pay_xaf'] ?? null)
        @php($ngnMinor = $t['receive_ngn_minor'] ?? null)
        @php($ngnMajor = $ngnMinor ? number_format($ngnMinor/100, 2) : null)
        <p>Hello {{ $user->name }},</p>
        <p>Your transfer has been initiated. Please approve the Mobile Money prompt to complete the payment.</p>
        <div class="card">
            <div class="row"><span class="label">Reference</span><strong>{{ $t['payin_ref'] ?? $id }}</strong></div>
            <div class="row"><span class="label">You Pay (XAF)</span><strong>{{ $totalPayXaf ? number_format($totalPayXaf) : ($amountXaf ? number_format($amountXaf) : '—') }}</strong></div>
            <div class="row"><span class="label">Recipient Gets (NGN)</span><strong>{{ $ngnMajor ?? '—' }}</strong></div>
            <div class="row"><span class="label">Recipient</span><strong>{{ ($t['recipient_account_name'] ?? 'Recipient') }} • {{ isset($t['recipient_account_number']) ? substr($t['recipient_account_number'],0,3).'•••'.substr($t['recipient_account_number'],-3) : '—' }}</strong></div>
        </div>
        <a class="cta" href="{{ $id ? route('transfer.receipt', $id) : route('dashboard') }}">View Receipt</a>
        <p style="font-size:13px;color:#6c757d">If you did not request this, please contact support immediately.</p>
    </div>
    <div class="footer">© {{ date('Y') }} TexaPay. Do not reply to this email.</div>
</div>
</body>
</html>
