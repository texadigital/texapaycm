<?php
return [
    'throttle_seconds' => (int) env('NOTIFY_THROTTLE_SECONDS', 60),
    'retention_days' => (int) env('NOTIFY_RETENTION_DAYS', 90),
    'quiet_hours' => [
        'enabled' => (bool) env('NOTIFY_QUIET_HOURS_ENABLED', false),
        'start' => env('NOTIFY_QUIET_START', '22:00'),
        'end' => env('NOTIFY_QUIET_END', '06:00'),
    ],
    'templates' => [
        'auth.login.success' => [
            'title' => 'Welcome back!',
            'message' => 'You have successfully logged in to your TexaPay account.',
        ],
        'auth.login.failed' => [
            'title' => 'Failed Login Attempt',
            'message' => 'We detected a failed login attempt on your account. If this wasn\'t you, please secure your account immediately.',
        ],
        'auth.login.new_device' => [
            'title' => 'New Device Login',
            'message' => 'Your account was accessed from a new device. If this wasn\'t you, please secure your account immediately.',
        ],
        'auth.password.reset.requested' => [
            'title' => 'Password Reset Code',
            'message' => 'Your password reset code was requested. If this wasn\'t you, please secure your account.',
        ],
        'auth.password.reset.success' => [
            'title' => 'Password Reset Successful',
            'message' => 'Your password has been reset successfully. You can now sign in with your new password.',
        ],
        'profile.updated' => [
            'title' => 'Profile Updated',
            'message' => 'Your profile information has been successfully updated.',
        ],
        'security.settings.updated' => [
            'title' => 'Security Settings Updated',
            'message' => 'Your security settings have been updated successfully.',
        ],
        'kyc.started' => [
            'title' => 'KYC Verification Started',
            'message' => 'Your identity verification process has been initiated. Please complete the required steps.',
        ],
        'kyc.completed' => [
            'title' => 'KYC Verification Completed',
            'message' => 'Congratulations! Your identity has been successfully verified.',
        ],
        'kyc.failed' => [
            'title' => 'KYC Verification Failed',
            'message' => 'Your identity verification was unsuccessful. Please try again with clear, valid documents.',
        ],
        'transfer.quote.created' => [
            'title' => 'Quote Created',
            'message' => 'Your transfer quote has been created. Please confirm the payment within the time limit.',
        ],
        'transfer.quote.expired' => [
            'title' => 'Quote Expired',
            'message' => 'Your transfer quote has expired. Please create a new quote to continue.',
        ],
        'transfer.initiated' => [
            'title' => 'Transfer Initiated',
            'message' => 'Your transfer has been initiated. Please complete the payment on your mobile money app.',
        ],
        'transfer.payin.success' => [
            'title' => 'Payment Received',
            'message' => 'Your payment has been received successfully. Processing payout to recipient.',
        ],
        'transfer.payin.failed' => [
            'title' => 'Payment Failed',
            'message' => 'Your payment could not be processed. Please try again or contact support.',
        ],
        'transfer.payout.success' => [
            'title' => 'Transfer Completed',
            'message' => 'Your transfer has been completed successfully. The recipient has received the funds.',
        ],
        'transfer.payout.failed' => [
            'title' => 'Transfer Failed',
            'message' => 'Your transfer could not be completed. A refund has been initiated automatically.',
        ],
        'transfer.refund.initiated' => [
            'title' => 'Refund Initiated',
            'message' => 'A refund has been initiated for your failed transfer. You will receive your money back shortly.',
        ],
        'transfer.refund.completed' => [
            'title' => 'Refund Completed',
            'message' => 'Your refund has been processed successfully. The funds have been returned to your account.',
        ],
        'protected.locked' => [
            'title' => 'Funds Held in Escrow',
            'message' => 'Your payment has been received and is being held securely. You can approve release when satisfied.',
        ],
        'protected.approval.requested' => [
            'title' => 'Release Requested',
            'message' => 'The seller has requested a release of funds. Please review and approve if satisfied.',
        ],
        'protected.approved' => [
            'title' => 'Funds Released',
            'message' => 'You approved the release. The seller is being paid now.',
        ],
        'protected.auto_release' => [
            'title' => 'Funds Auto-Released',
            'message' => 'Funds were released automatically after the waiting period.',
        ],
        'protected.disputed' => [
            'title' => 'Dispute Opened',
            'message' => 'You opened a dispute. Our team will review and keep the funds on hold.',
        ],
        'protected.payout.success' => [
            'title' => 'Escrow Completed',
            'message' => 'Funds have been paid to the seller. Thank you for using Texa Protected.',
        ],
        'protected.payout.failed' => [
            'title' => 'Payout Issue',
            'message' => 'We could not complete the payout to the seller yet. We are retrying and will update you.',
        ],
        'support.ticket.created' => [
            'title' => 'Support Ticket Created',
            'message' => 'Your support ticket has been created. We will respond within 24 hours.',
        ],
        'support.ticket.replied' => [
            'title' => 'Support Reply',
            'message' => 'You have received a reply to your support ticket.',
        ],
        'support.ticket.closed' => [
            'title' => 'Support Ticket Closed',
            'message' => 'Your support ticket has been closed. If you need further assistance, please create a new ticket.',
        ],
        'limits.warning.daily' => [
            'title' => 'Daily Limit Warning',
            'message' => 'You are approaching your daily transaction limit. Please monitor your usage.',
        ],
        'limits.warning.monthly' => [
            'title' => 'Monthly Limit Warning',
            'message' => 'You are approaching your monthly transaction limit. Please monitor your usage.',
        ],
        'limits.exceeded' => [
            'title' => 'Transaction Limit Exceeded',
            'message' => 'You have exceeded your transaction limit. Please contact support to increase your limits.',
        ],
    ],
];
