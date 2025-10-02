TexaPay - Refund Initiated

Hello {{ $user->name }},
We've initiated a refund for your failed transfer.

Refund ID: {{ $notification->payload['refund_id'] ?? 'N/A' }}
Original Reference: @php($t=$notification->payload['transfer']??[]) {{ $t['payin_ref'] ?? ($t['id'] ?? 'N/A') }}

View Receipt: {{ isset($t['id']) ? route('transfer.receipt',$t['id']) : route('dashboard') }}
