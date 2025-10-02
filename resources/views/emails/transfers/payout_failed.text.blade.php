TexaPay - Transfer Failed

Hello {{ $user->name }},
Your transfer could not be completed. A refund will be initiated automatically if payment was received.

Reason: {{ $notification->payload['reason'] ?? 'Unknown error' }}
Reference: @php($t=$notification->payload['transfer']??[]) {{ $t['payout_ref'] ?? ($t['payin_ref'] ?? ($t['id'] ?? 'N/A')) }}

View: {{ isset($t['id']) ? route('transfer.receipt',$t['id']) : route('dashboard') }}
