<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $notification->title }}</title>
    <style>
        body { margin:0; padding:0; background:#f2f4f7; }
        .wrapper { width:100%; table-layout:fixed; background:#f2f4f7; padding:20px 0; }
        .main { background:#ffffff; margin:0 auto; width:100%; max-width:640px; border-radius:12px; overflow:hidden; box-shadow:0 1px 3px rgba(16,24,40,.1); }
        .brand { background:#0f172a; color:#fff; padding:24px; text-align:center; }
        .brand h1 { margin:0; font:600 20px/1.2 -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica, Arial; }
        .preheader { display:none!important; visibility:hidden; opacity:0; color:transparent; height:0; width:0; }
        .content { padding:28px 28px 10px; color:#101828; font:400 16px/1.6 -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica, Arial; }
        .title { margin:0 0 6px; font:700 20px/1.3 -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica, Arial; color:#0f172a; }
        .message { margin:12px 0 16px; }
        .details { background:#f9fafb; border:1px solid #e5e7eb; border-left:4px solid #0ea5e9; border-radius:8px; padding:14px 16px; margin:16px 0; }
        .details h3 { margin:0 0 8px; font:600 14px/1.2 -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica, Arial; color:#0f172a; }
        .details ul { margin:0; padding-left:18px; }
        .cta { padding:0 28px 28px; }
        .btn { display:inline-block; background:#0ea5e9; color:#fff !important; text-decoration:none; padding:12px 20px; border-radius:8px; font:600 14px/1 -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica, Arial; }
        .footer { padding:18px 28px 26px; color:#667085; font:400 12px/1.5 -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica, Arial; }
        @media (max-width: 480px) { .content, .cta, .footer { padding-left:16px; padding-right:16px; } }
    </style>
</head>
<body>
    <span class="preheader">{{ $notification->title }}</span>
    <table role="presentation" class="wrapper" cellpadding="0" cellspacing="0" width="100%">
        <tr>
            <td align="center">
                <table role="presentation" class="main" cellpadding="0" cellspacing="0">
                    <tr>
                        <td class="brand">
                            <h1>TexaPay</h1>
                        </td>
                    </tr>
                    <tr>
                        <td class="content">
                            <h2 class="title">{{ $notification->title }}</h2>
                            <p class="message">Hello {{ $user->name }},</p>
                            <p class="message">{{ $notification->message }}</p>

                            @if($notification->payload && count($notification->payload) > 0)
                                <div class="details">
                                    <h3>Details</h3>
                                    <ul>
                                        @foreach($notification->payload as $key => $value)
                                            @if(is_string($value) || is_numeric($value))
                                                <li><strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong> {{ $value }}</li>
                                            @endif
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="cta">
                            @if($notification->type === 'transfer.initiated' || $notification->type === 'transfer.payout.success')
                                <a href="{{ route('transfer.receipt', $notification->payload['transfer']['id'] ?? '#') }}" class="btn">View Transfer Details</a>
                            @elseif($notification->type === 'support.ticket.created')
                                <a href="{{ route('support.tickets') }}" class="btn">View Support Tickets</a>
                            @elseif(str_starts_with($notification->type, 'kyc.'))
                                <a href="{{ route('kyc.status') }}" class="btn">Check KYC Status</a>
                            @elseif(str_starts_with($notification->type, 'limits.'))
                                <a href="{{ route('profile.limits') }}" class="btn">View Transaction Limits</a>
                            @else
                                <a href="{{ route('dashboard') }}" class="btn">Go to Dashboard</a>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="footer">
                            <p>This is an automated message from TexaPay. Please do not reply to this email.</p>
                            <p>If you have any questions, please contact our support team.</p>
                            <p>&copy; {{ date('Y') }} TexaPay. All rights reserved.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>


