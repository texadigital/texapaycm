TexaPay - Refund Completed

Hello {{ $user->name }},
Your refund has been processed successfully.

Refund ID: {{ $notification->payload['refund_id'] ?? 'N/A' }}
Original Reference: @php($t=$notification->payload['transfer']??[]) {{ $t['payin_ref'] ?? ($t['id'] ?? 'N/A') }}

View Receipt: {{ isset($t['id']) ? route('transfer.receipt',$t['id']) : route('dashboard') }}
