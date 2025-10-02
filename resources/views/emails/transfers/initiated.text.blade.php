TexaPay - Transfer Initiated

Hello {{ $user->name }},
Your transfer has been initiated. Please approve the Mobile Money prompt to complete payment.

@php($t = $notification->payload['transfer'] ?? [])
Reference: {{ $t['payin_ref'] ?? ($t['id'] ?? 'N/A') }}
You Pay (XAF): {{ isset($t['total_pay_xaf']) ? number_format($t['total_pay_xaf']) : (isset($t['amount_xaf']) ? number_format($t['amount_xaf']) : '—') }}
Recipient Gets (NGN): @php($ngn = isset($t['receive_ngn_minor']) ? number_format($t['receive_ngn_minor']/100, 2) : null) {{ $ngn ?? '—' }}
Recipient: {{ $t['recipient_account_name'] ?? 'Recipient' }} • @php($acct=$t['recipient_account_number']??null) {{ $acct ? (substr($acct,0,3).'•••'.substr($acct,-3)) : '—' }}

View Receipt: {{ isset($t['id']) ? route('transfer.receipt', $t['id']) : route('dashboard') }}
