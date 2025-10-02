<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $notification->title }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #007bff;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #f8f9fa;
            padding: 30px;
            border-radius: 0 0 8px 8px;
        }
        .message {
            font-size: 16px;
            margin-bottom: 20px;
        }
        .cta-button {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 4px;
            margin: 20px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            font-size: 14px;
            color: #6c757d;
        }
        .notification-details {
            background-color: white;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
            border-left: 4px solid #007bff;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>TexaPay</h1>
        <h2>{{ $notification->title }}</h2>
    </div>
    
    <div class="content">
        <div class="message">
            <p>Hello {{ $user->name }},</p>
            
            <p>{{ $notification->message }}</p>
        </div>

        @if($notification->payload && count($notification->payload) > 0)
        <div class="notification-details">
            <h3>Details:</h3>
            <ul>
                @foreach($notification->payload as $key => $value)
                    @if(is_string($value) || is_numeric($value))
                        <li><strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong> {{ $value }}</li>
                    @endif
                @endforeach
            </ul>
        </div>
        @endif

        @if($notification->type === 'transfer.initiated' || $notification->type === 'transfer.payout.success')
            <a href="{{ route('transfer.receipt', $notification->payload['transfer']['id'] ?? '#') }}" class="cta-button">
                View Transfer Details
            </a>
        @elseif($notification->type === 'support.ticket.created')
            <a href="{{ route('support.tickets') }}" class="cta-button">
                View Support Tickets
            </a>
        @elseif(str_starts_with($notification->type, 'kyc.'))
            <a href="{{ route('kyc.status') }}" class="cta-button">
                Check KYC Status
            </a>
        @elseif(str_starts_with($notification->type, 'limits.'))
            <a href="{{ route('profile.limits') }}" class="cta-button">
                View Transaction Limits
            </a>
        @else
            <a href="{{ route('dashboard') }}" class="cta-button">
                Go to Dashboard
            </a>
        @endif
    </div>

    <div class="footer">
        <p>This is an automated message from TexaPay. Please do not reply to this email.</p>
        <p>If you have any questions, please contact our support team.</p>
        <p>&copy; {{ date('Y') }} TexaPay. All rights reserved.</p>
    </div>
</body>
</html>


