TexaPay - Transfer Completed

Hello {{ $user->name }},
Your transfer was completed successfully.

@php($t=$notification->payload['transfer']??[])
Amount (NGN): @php($ngn=$t['receive_ngn_minor']??null) {{ $ngn?number_format($ngn/100,2):'—' }}
Recipient: {{ $t['recipient_account_name'] ?? 'Recipient' }} • @php($acct=$t['recipient_account_number']??null) {{ $acct ? (substr($acct,0,3).'•••'.substr($acct,-3)) : '—' }}
Reference: {{ $t['payout_ref'] ?? ($t['payin_ref'] ?? ($t['id'] ?? 'N/A')) }}

View Receipt: {{ isset($t['id']) ? route('transfer.receipt',$t['id']) : route('dashboard') }}
