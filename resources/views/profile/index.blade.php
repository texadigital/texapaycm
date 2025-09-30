<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - TexaPay</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0f172a; color: #f8fafc; line-height: 1.6; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { background: #1e293b; padding: 20px; border-radius: 12px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; }
        .btn { background: #3b82f6; color: white; padding: 10px 16px; border: none; border-radius: 6px; text-decoration: none; display: inline-block; cursor: pointer; }
        .btn:hover { background: #2563eb; }
        .btn-secondary { background: #6b7280; }
        .btn-secondary:hover { background: #4b5563; }
        .card { background: #1e293b; border-radius: 12px; padding: 20px; margin-bottom: 20px; border: 1px solid #334155; }
        .grid { display: grid; gap: 20px; }
        .grid-2 { grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); }
        .grid-3 { grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); }
        .stat-card { background: #0f172a; padding: 16px; border-radius: 8px; border: 1px solid #1e293b; }
        .progress-bar { background: #374151; height: 8px; border-radius: 4px; overflow: hidden; margin-top: 8px; }
        .progress-fill { height: 100%; transition: width 0.3s ease; }
        .text-blue { color: #60a5fa; }
        .text-green { color: #34d399; }
        .text-yellow { color: #fbbf24; }
        .text-red { color: #ef4444; }
        .text-gray { color: #9ca3af; }
        .text-sm { font-size: 14px; }
        .text-xs { font-size: 12px; }
        .font-semibold { font-weight: 600; }
        .mb-2 { margin-bottom: 8px; }
        .mb-4 { margin-bottom: 16px; }
        .nav-tabs { display: flex; gap: 8px; margin-bottom: 24px; }
        .nav-tab { padding: 12px 20px; background: #374151; border-radius: 8px; text-decoration: none; color: #d1d5db; }
        .nav-tab.active { background: #3b82f6; color: white; }
        .alert { padding: 12px; border-radius: 8px; margin-bottom: 16px; }
        .alert-warning { background: #f59e0b; color: white; }
        .alert-critical { background: #dc2626; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>Profile</h1>
                <p class="text-gray">Manage your account settings and view transaction limits</p>
            </div>
            <div style="display: flex; gap: 12px;">
                <a href="{{ route('dashboard') }}" class="btn-secondary btn">← Dashboard</a>
                <a href="{{ route('transfer.bank') }}" class="btn">Send Money</a>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="nav-tabs">
            <a href="{{ route('profile.index') }}" class="nav-tab active">Overview</a>
            <a href="{{ route('profile.limits') }}" class="nav-tab">Transaction Limits</a>
        </div>

        <!-- Limit Warnings -->
        @if(!empty($limitWarnings))
            @foreach($limitWarnings as $warning)
                <div class="alert {{ $warning['level'] === 'critical' ? 'alert-critical' : 'alert-warning' }}">
                    <strong>{{ $warning['level'] === 'critical' ? '⚠️ Critical' : '⚠️ Warning' }}:</strong> {{ $warning['message'] }}
                </div>
            @endforeach
        @endif

        <!-- Profile Overview -->
        <div class="grid grid-2">
            <!-- User Information -->
            <div class="card">
                <h3 class="text-blue mb-4">👤 Account Information</h3>
                <div style="display: grid; gap: 12px;">
                    <div>
                        <div class="text-gray text-sm">Name</div>
                        <div class="font-semibold">{{ $user->name }}</div>
                    </div>
                    <div>
                        <div class="text-gray text-sm">Email</div>
                        <div class="font-semibold">{{ $user->email }}</div>
                    </div>
                    <div>
                        <div class="text-gray text-sm">Phone</div>
                        <div class="font-semibold">{{ $user->phone ?? 'Not provided' }}</div>
                    </div>
                    <div>
                        <div class="text-gray text-sm">Member Since</div>
                        <div class="font-semibold">{{ $user->created_at->format('M d, Y') }}</div>
                    </div>
                </div>
            </div>

            <!-- Quick Limit Status -->
            @if(isset($limitStatus['limits']))
            <div class="card">
                <h3 class="text-green mb-4">📊 Today's Usage</h3>
                <div style="display: grid; gap: 16px;">
                    <!-- Daily Amount -->
                    <div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                            <span class="text-gray text-sm">Daily Amount</span>
                            <span class="text-sm font-semibold">{{ number_format($limitStatus['usage']['daily_amount']) }} / {{ number_format($limitStatus['limits']['daily_limit_xaf']) }} XAF</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="background: {{ $limitStatus['utilization']['daily_percentage'] >= 80 ? '#ef4444' : '#10b981' }}; width: {{ min(100, $limitStatus['utilization']['daily_percentage']) }}%;"></div>
                        </div>
                        <div class="text-gray text-xs" style="margin-top: 4px;">{{ number_format($limitStatus['utilization']['daily_percentage'], 1) }}% used</div>
                    </div>

                    <!-- Daily Count -->
                    <div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                            <span class="text-gray text-sm">Daily Transactions</span>
                            <span class="text-sm font-semibold">{{ $limitStatus['usage']['daily_count'] }} / {{ $limitStatus['limits']['daily_count_limit'] }}</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="background: {{ $limitStatus['utilization']['daily_count_percentage'] >= 80 ? '#ef4444' : '#10b981' }}; width: {{ min(100, $limitStatus['utilization']['daily_count_percentage']) }}%;"></div>
                        </div>
                        <div class="text-gray text-xs" style="margin-top: 4px;">{{ number_format($limitStatus['utilization']['daily_count_percentage'], 1) }}% used</div>
                    </div>
                </div>
                
                <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #374151;">
                    <a href="{{ route('profile.limits') }}" class="btn" style="width: 100%; text-align: center;">View Detailed Limits</a>
                </div>
            </div>
            @endif
        </div>

        <!-- 30-Day Statistics -->
        @if(isset($userStats))
        <div class="card">
            <h3 class="text-yellow mb-4">📈 30-Day Statistics</h3>
            <div class="grid grid-3">
                <div class="stat-card">
                    <div class="text-gray text-sm">Total Sent</div>
                    <div class="font-semibold" style="font-size: 18px;">{{ number_format($userStats['successful_amount']) }} XAF</div>
                </div>
                <div class="stat-card">
                    <div class="text-gray text-sm">Success Rate</div>
                    <div class="font-semibold" style="font-size: 18px; color: {{ $userStats['success_rate'] >= 90 ? '#10b981' : ($userStats['success_rate'] >= 70 ? '#f59e0b' : '#ef4444') }};">{{ number_format($userStats['success_rate'], 1) }}%</div>
                </div>
                <div class="stat-card">
                    <div class="text-gray text-sm">Transactions</div>
                    <div class="font-semibold" style="font-size: 18px;">{{ $userStats['successful_count'] }}</div>
                </div>
                <div class="stat-card">
                    <div class="text-gray text-sm">Active Days</div>
                    <div class="font-semibold" style="font-size: 18px;">{{ $userStats['active_days'] }}</div>
                </div>
                <div class="stat-card">
                    <div class="text-gray text-sm">Avg Transaction</div>
                    <div class="font-semibold" style="font-size: 18px;">{{ number_format($userStats['average_transaction_amount']) }} XAF</div>
                </div>
                <div class="stat-card">
                    <div class="text-gray text-sm">Daily Average</div>
                    <div class="font-semibold" style="font-size: 18px;">{{ number_format($userStats['average_daily_amount']) }} XAF</div>
                </div>
            </div>
        </div>
        @endif

        <!-- Quick Actions -->
        <div class="card">
            <h3 class="mb-4">🚀 Quick Actions</h3>
            <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                <a href="{{ route('transfer.bank') }}" class="btn">Send Money</a>
                <a href="{{ route('transactions.index') }}" class="btn-secondary btn">View Transactions</a>
                <a href="{{ route('profile.limits') }}" class="btn-secondary btn">Manage Limits</a>
            </div>
        </div>
    </div>
</body>
</html>
