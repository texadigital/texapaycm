TexaPay - Payment Failed

Hello {{ $user->name }},
Your payment could not be processed.

Reason: {{ $notification->payload['reason'] ?? 'Unknown error' }}
Failure Code: {{ $notification->payload['failure_code'] ?? '—' }}

View: {{ isset(($notification->payload['transfer'] ?? [])['id']) ? route('transfer.receipt', ($notification->payload['transfer'] ?? [])['id']) : route('dashboard') }}
