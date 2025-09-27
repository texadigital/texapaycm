<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Transfer Receipt - TexaPay</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-light: #818cf8;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #3b82f6;
            --dark: #0f172a;
            --dark-light: #1e293b;
            --light: #f8fafc;
            --muted: #94a3b8;
            --border: #334155;
        }
        
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'Inter', system-ui, -apple-system, sans-serif; 
            background: #020617; 
            color: #e2e8f0; 
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }
        
        .container { 
            max-width: 640px; 
            margin: 2rem auto; 
            padding: 0 1rem;
        }
        
        .receipt-card {
            background: #0f172a;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            border: 1px solid #1e293b;
            margin-bottom: 1.5rem;
        }
        
        .receipt-header {
            padding: 2rem;
            text-align: center;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-bottom: 1px solid #1e293b;
        }
        
        .status-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            display: inline-block;
        }
        
        .status-icon.success { color: var(--success); }
        .status-icon.pending { color: var(--warning); animation: pulse 2s infinite; }
        .status-icon.failed { color: var(--danger); }
        
        @keyframes pulse {
            0% { opacity: 0.6; }
            50% { opacity: 1; }
            100% { opacity: 0.6; }
        }
        
        .receipt-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #f8fafc;
        }
        
        .receipt-subtitle {
            color: var(--muted);
            font-size: 0.875rem;
        }
        
        .receipt-body {
            padding: 1.5rem;
        }
        
        .section {
            margin-bottom: 1.5rem;
        }
        
        .section:last-child {
            margin-bottom: 0;
        }
        
        .section-title {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--muted);
            margin-bottom: 1rem;
            font-weight: 600;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #1e293b;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            color: var(--muted);
            font-size: 0.875rem;
        }
        
        .detail-value {
            font-weight: 500;
            text-align: right;
        }
        
        .amount-large {
            font-size: 1.5rem;
            font-weight: 700;
            color: #f8fafc;
        }
        
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: capitalize;
        }
        
        .badge-success { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .badge-warning { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .badge-danger { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
        .badge-info { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        
        .timeline {
            position: relative;
            padding-left: 1.5rem;
            margin-top: 1rem;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 0.5rem;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #1e293b;
        }
        
        .timeline-item {
            position: relative;
            padding-left: 1.5rem;
            padding-bottom: 1.5rem;
        }
        
        .timeline-item:last-child {
            padding-bottom: 0;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -0.25rem;
            top: 0.25rem;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #3b82f6;
            border: 2px solid #0f172a;
        }
        
        .timeline-item.success::before { background: #10b981; }
        .timeline-item.failed::before { background: #ef4444; }
        .timeline-item.pending::before { background: #f59e0b; animation: pulse 2s infinite; }
        
        .timeline-time {
            font-size: 0.75rem;
            color: var(--muted);
            margin-bottom: 0.25rem;
        }
        
        .timeline-content {
            font-size: 0.875rem;
            color: #e2e8f0;
        }
        
        .timeline-note {
            font-size: 0.75rem;
            color: var(--muted);
            margin-top: 0.25rem;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.625rem 1.25rem;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.875rem;
            line-height: 1.25rem;
            transition: all 0.2s;
            cursor: pointer;
            text-decoration: none;
            border: 1px solid transparent;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: #4338ca;
        }
        
        .btn-outline {
            background: transparent;
            border: 1px solid #334155;
            color: #e2e8f0;
        }
        
        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        
        .btn i {
            margin-right: 0.5rem;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: flex-start;
        }
        
        .alert i {
            margin-right: 0.75rem;
            font-size: 1.25rem;
            margin-top: 0.125rem;
        }
        
        .alert-info {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.2);
            color: #3b82f6;
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: #10b981;
        }
        
        .alert-warning {
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.2);
            color: #f59e0b;
        }
        
        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }
        
        .alert-content {
            flex: 1;
        }
        
        .alert-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .alert-message {
            font-size: 0.875rem;
            opacity: 0.9;
        }
        
        @media (max-width: 480px) {
            .container {
                padding: 0 1rem;
                margin: 1rem auto;
            }
            
            .receipt-header {
                padding: 1.5rem 1rem;
            }
            
            .receipt-body {
                padding: 1.25rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    @php
        // Determine the status for display
        $isCompleted = in_array($transfer->status, ['completed', 'payout_success']);
        $isFailed = in_array($transfer->status, ['failed', 'rejected', 'expired']);
        $isPending = !$isCompleted && !$isFailed;
        
        // Get the last timeline event for additional context
        $lastEvent = collect($transfer->timeline ?? [])->last();
        $lastEventTime = $lastEvent['at'] ?? null;
    @endphp

    @if ($isPending)
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-refresh the page every 10 seconds if still pending
            const refreshInterval = setInterval(function() {
                fetch(window.location.href, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                })
                .then(response => response.json())
                .then(data => {
                    if (data.redirect) {
                        window.location.reload();
                    }
                });
            }, 10000);
            
            // Clean up interval when leaving the page
            window.addEventListener('beforeunload', function() {
                clearInterval(refreshInterval);
            });
        });
    </script>
    @endif

    <div class="container">
        <div class="receipt-card">
            <!-- Header with status -->
            <div class="receipt-header">
                @if($isCompleted)
                    <div class="status-icon success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h1 class="receipt-title">Transfer Completed</h1>
                    <p class="receipt-subtitle">Your transfer was successful</p>
                @elseif($isFailed)
                    <div class="status-icon failed">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <h1 class="receipt-title">Transfer Failed</h1>
                    <p class="receipt-subtitle">Your transfer could not be completed</p>
                @else
                    <div class="status-icon pending">
                        <i class="fas fa-sync-alt fa-spin"></i>
                    </div>
                    <h1 class="receipt-title">Transfer in Progress</h1>
                    <p class="receipt-subtitle">We're processing your transfer</p>
                @endif
            </div>

            <div class="receipt-body">
                <!-- Status Alert -->
                @if($isPending)
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <div class="alert-content">
                            <div class="alert-title">Processing Your Transfer</div>
                            <div class="alert-message">
                                @if($transfer->status === 'payin_pending')
                                    We're waiting for confirmation of your payment. This may take a few minutes.
                                @elseif($transfer->status === 'payout_pending')
                                    Your payment was received and we're processing the transfer to the recipient.
                                @else
                                    Your transfer is being processed. This page will update automatically.
                                @endif
                            </div>
                        </div>
                    </div>
                @elseif($isFailed)
                    @php
                        $errorMessage = $lastEvent['reason'] ?? 'An error occurred while processing your transfer.';
                    @endphp
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div class="alert-content">
                            <div class="alert-title">Transfer Failed</div>
                            <div class="alert-message">{{ $errorMessage }}</div>
                        </div>
                    </div>
                @elseif($isCompleted)
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <div class="alert-content">
                            <div class="alert-title">Transfer Completed Successfully</div>
                            <div class="alert-message">
                                The funds have been sent to the recipient's account.
                                @if($transfer->payout_completed_at)
                                    <br>Completed on {{ \Carbon\Carbon::parse($transfer->payout_completed_at)->format('M j, Y \a\t g:i A') }}
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Transfer Details -->
                <div class="section">
                    <div class="section-title">Transfer Details</div>
                    <div class="detail-row">
                        <span class="detail-label">Reference</span>
                        <span class="detail-value">{{ $transfer->id }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Status</span>
                        <span class="detail-value">
                            @if($isCompleted)
                                <span class="badge badge-success">Completed</span>
                            @elseif($isFailed)
                                <span class="badge badge-danger">Failed</span>
                            @else
                                <span class="badge badge-warning">In Progress</span>
                            @endif
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Date</span>
                        <span class="detail-value">{{ $transfer->created_at->format('M j, Y \a\t g:i A') }}</span>
                    </div>
                </div>

                <!-- Amount Details -->
                <div class="section">
                    <div class="section-title">Amount Details</div>
                    <div class="detail-row">
                        <span class="detail-label">You send</span>
                        <span class="detail-value">{{ number_format($transfer->amount_xaf, 2) }} XAF</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Fee</span>
                        <span class="detail-value">{{ number_format($transfer->fee_total_xaf, 2) }} XAF</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Total</span>
                        <span class="detail-value">{{ number_format($transfer->total_pay_xaf, 2) }} XAF</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Exchange Rate</span>
                        <span class="detail-value">1 XAF = {{ number_format($transfer->adjusted_rate_xaf_to_ngn, 6) }} NGN</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Recipient gets</span>
                        <span class="detail-value amount-large">{{ number_format($transfer->receive_ngn_minor / 100, 2) }} NGN</span>
                    </div>
                </div>

                <!-- Recipient Details -->
                <div class="section">
                    <div class="section-title">Recipient Details</div>
                    <div class="detail-row">
                        <span class="detail-label">Name</span>
                        <span class="detail-value">{{ $transfer->recipient_account_name }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Bank</span>
                        <span class="detail-value">{{ $transfer->recipient_bank_name }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Account Number</span>
                        <span class="detail-value">{{ substr($transfer->recipient_account_number, 0, 2) . '••••' . substr($transfer->recipient_account_number, -4) }}</span>
                    </div>
                </div>

                <!-- Timeline -->
                <div class="section">
                    <div class="section-title">Transaction Timeline</div>
                    <div class="timeline">
                        @if(is_array($transfer->timeline) && count($transfer->timeline) > 0)
                            @foreach($transfer->timeline as $event)
                                @php
                                    $eventType = 'info';
                                    if (str_contains($event['state'] ?? '', 'fail') || str_contains($event['state'] ?? '', 'reject')) {
                                        $eventType = 'failed';
                                    } elseif (str_contains($event['state'] ?? '', 'complete') || str_contains($event['state'] ?? '', 'success')) {
                                        $eventType = 'success';
                                    } elseif (str_contains($event['state'] ?? '', 'pending') || str_contains($event['state'] ?? '', 'process')) {
                                        $eventType = 'pending';
                                    }
                                @endphp
                                <div class="timeline-item {{ $eventType }}">
                                    <div class="timeline-time">
                                        {{ \Carbon\Carbon::parse($event['at'])->format('M j, Y g:i A') }}
                                    </div>
                                    <div class="timeline-content">
                                        {{ ucwords(str_replace('_', ' ', $event['state'])) }}
                                        @if(!empty($event['reason']))
                                            <div class="timeline-note">{{ $event['reason'] }}</div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="text-muted">No timeline events available.</div>
                        @endif
                    </div>
                </div>

                <!-- Actions -->
                <div class="section">
                    <div class="action-buttons">
                        <a href="{{ route('transfer.bank') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> New Transfer
                        </a>
                        
                        @if($isCompleted)
                            <button onclick="window.print()" class="btn btn-outline">
                                <i class="fas fa-print"></i> Print Receipt
                            </button>
                        @elseif($isFailed)
                            <a href="{{ route('support') }}" class="btn btn-outline">
                                <i class="fas fa-question-circle"></i> Get Help
                            </a>
                        @endif
                        
                        @if($isPending && $transfer->status === 'payout_pending')
                            <form method="post" action="{{ route('transfer.payout.status', $transfer) }}" class="flex-1">
                                @csrf
                                <button type="submit" class="btn btn-outline w-full">
                                    <i class="fas fa-sync-alt"></i> Check Status
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        
        <div class="text-center text-sm text-muted mb-8">
            <p>Need help? <a href="{{ route('support') }}" class="text-primary-light hover:underline">Contact Support</a></p>
            <p class="text-xs mt-2">Reference: {{ $transfer->id }}</p>
        </div>
    </div>

    @if($isPending)
    <div id="auto-refresh-notice" class="fixed bottom-4 left-1/2 transform -translate-x-1/2 bg-dark-light text-xs text-muted px-4 py-2 rounded-full border border-border shadow-lg">
        <i class="fas fa-sync-alt fa-spin mr-1"></i> Auto-updating...
    </div>
    @endif
</body>
</html>
