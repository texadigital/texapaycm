TexaPay - Payment Received

Hello {{ $user->name }},
Your payment was received successfully. We are processing your payout to the recipient.

@php($t=$notification->payload['transfer']??[])
You Paid (XAF): {{ isset($t['total_pay_xaf'])?number_format($t['total_pay_xaf']):'—' }}
Recipient Gets (NGN): @php($ngn=$t['receive_ngn_minor']??null) {{ $ngn?number_format($ngn/100,2):'—' }}
Reference: {{ $t['payin_ref'] ?? ($t['id'] ?? 'N/A') }}

View Receipt: {{ isset($t['id']) ? route('transfer.receipt',$t['id']) : route('dashboard') }}
