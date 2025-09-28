<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Limits - TexaPay</title>
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
        .grid-2 { grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); }
        .grid-3 { grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); }
        .limit-card { background: #0f172a; padding: 20px; border-radius: 12px; border: 1px solid #1e293b; }
        .progress-bar { background: #374151; height: 12px; border-radius: 6px; overflow: hidden; margin: 8px 0; }
        .progress-fill { height: 100%; transition: width 0.3s ease; border-radius: 6px; }
        .text-blue { color: #60a5fa; }
        .text-green { color: #34d399; }
        .text-yellow { color: #fbbf24; }
        .text-red { color: #ef4444; }
        .text-gray { color: #9ca3af; }
        .text-sm { font-size: 14px; }
        .text-xs { font-size: 12px; }
        .text-lg { font-size: 18px; }
        .font-semibold { font-weight: 600; }
        .font-bold { font-weight: 700; }
        .mb-2 { margin-bottom: 8px; }
        .mb-4 { margin-bottom: 16px; }
        .nav-tabs { display: flex; gap: 8px; margin-bottom: 24px; }
        .nav-tab { padding: 12px 20px; background: #374151; border-radius: 8px; text-decoration: none; color: #d1d5db; }
        .nav-tab.active { background: #3b82f6; color: white; }
        .alert { padding: 12px; border-radius: 8px; margin-bottom: 16px; }
        .alert-warning { background: #f59e0b; color: white; }
        .alert-critical { background: #dc2626; color: white; }
        .stat-row { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #374151; }
        .stat-row:last-child { border-bottom: none; }
        .tabs { display: flex; gap: 8px; margin-bottom: 20px; }
        .tab { padding: 8px 16px; background: #374151; border-radius: 6px; text-decoration: none; color: #d1d5db; cursor: pointer; }
        .tab.active { background: #3b82f6; color: white; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>Transaction Limits</h1>
                <p class="text-gray">Monitor your daily and monthly transaction limits</p>
            </div>
            <div style="display: flex; gap: 12px;">
                <a href="{{ route('profile.index') }}" class="btn-secondary btn">← Profile</a>
                <a href="{{ route('transfer.bank') }}" class="btn">Send Money</a>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="nav-tabs">
            <a href="{{ route('profile.index') }}" class="nav-tab">Overview</a>
            <a href="{{ route('profile.limits') }}" class="nav-tab active">Transaction Limits</a>
        </div>

        <!-- Limit Warnings -->
        @if(!empty($limitWarnings))
            @foreach($limitWarnings as $warning)
                <div class="alert {{ $warning['level'] === 'critical' ? 'alert-critical' : 'alert-warning' }}">
                    <strong>{{ $warning['level'] === 'critical' ? '⚠️ Critical' : '⚠️ Warning' }}:</strong> {{ $warning['message'] }}
                </div>
            @endforeach
        @endif

        <!-- Current Limits Overview -->
        @if(isset($limitStatus['limits']))
        <div class="grid grid-2">
            <!-- Daily Limits -->
            <div class="limit-card">
                <h3 class="text-blue mb-4">📅 Daily Limits</h3>
                
                <!-- Daily Amount Limit -->
                <div class="mb-4">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span class="font-semibold">Amount Limit</span>
                        <span class="text-lg font-bold">{{ number_format($limitStatus['limits']['daily_limit_xaf']) }} XAF</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <span class="text-gray text-sm">Used Today</span>
                        <span class="text-sm">{{ number_format($limitStatus['usage']['daily_amount']) }} XAF</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="background: {{ $limitStatus['utilization']['daily_percentage'] >= 95 ? '#ef4444' : ($limitStatus['utilization']['daily_percentage'] >= 80 ? '#f59e0b' : '#10b981') }}; width: {{ min(100, $limitStatus['utilization']['daily_percentage']) }}%;"></div>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-top: 4px;">
                        <span class="text-xs text-gray">{{ number_format($limitStatus['utilization']['daily_percentage'], 1) }}% used</span>
                        <span class="text-xs font-semibold" style="color: #10b981;">{{ number_format($limitStatus['remaining']['daily_amount']) }} XAF remaining</span>
                    </div>
                </div>

                <!-- Daily Count Limit -->
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span class="font-semibold">Transaction Count</span>
                        <span class="text-lg font-bold">{{ $limitStatus['limits']['daily_count_limit'] }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <span class="text-gray text-sm">Used Today</span>
                        <span class="text-sm">{{ $limitStatus['usage']['daily_count'] }}</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="background: {{ $limitStatus['utilization']['daily_count_percentage'] >= 95 ? '#ef4444' : ($limitStatus['utilization']['daily_count_percentage'] >= 80 ? '#f59e0b' : '#10b981') }}; width: {{ min(100, $limitStatus['utilization']['daily_count_percentage']) }}%;"></div>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-top: 4px;">
                        <span class="text-xs text-gray">{{ number_format($limitStatus['utilization']['daily_count_percentage'], 1) }}% used</span>
                        <span class="text-xs font-semibold" style="color: #10b981;">{{ $limitStatus['remaining']['daily_count'] }} remaining</span>
                    </div>
                </div>
            </div>

            <!-- Monthly Limits -->
            <div class="limit-card">
                <h3 class="text-green mb-4">📊 Monthly Limits</h3>
                
                <!-- Monthly Amount Limit -->
                <div class="mb-4">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span class="font-semibold">Amount Limit</span>
                        <span class="text-lg font-bold">{{ number_format($limitStatus['limits']['monthly_limit_xaf']) }} XAF</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <span class="text-gray text-sm">Used This Month</span>
                        <span class="text-sm">{{ number_format($limitStatus['usage']['monthly_amount']) }} XAF</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="background: {{ $limitStatus['utilization']['monthly_percentage'] >= 95 ? '#ef4444' : ($limitStatus['utilization']['monthly_percentage'] >= 80 ? '#f59e0b' : '#10b981') }}; width: {{ min(100, $limitStatus['utilization']['monthly_percentage']) }}%;"></div>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-top: 4px;">
                        <span class="text-xs text-gray">{{ number_format($limitStatus['utilization']['monthly_percentage'], 1) }}% used</span>
                        <span class="text-xs font-semibold" style="color: #10b981;">{{ number_format($limitStatus['remaining']['monthly_amount']) }} XAF remaining</span>
                    </div>
                </div>

                <!-- Monthly Count Limit -->
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span class="font-semibold">Transaction Count</span>
                        <span class="text-lg font-bold">{{ $limitStatus['limits']['monthly_count_limit'] }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <span class="text-gray text-sm">Used This Month</span>
                        <span class="text-sm">{{ $limitStatus['usage']['monthly_count'] }}</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="background: {{ $limitStatus['utilization']['monthly_count_percentage'] >= 95 ? '#ef4444' : ($limitStatus['utilization']['monthly_count_percentage'] >= 80 ? '#f59e0b' : '#10b981') }}; width: {{ min(100, $limitStatus['utilization']['monthly_count_percentage']) }}%;"></div>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-top: 4px;">
                        <span class="text-xs text-gray">{{ number_format($limitStatus['utilization']['monthly_count_percentage'], 1) }}% used</span>
                        <span class="text-xs font-semibold" style="color: #10b981;">{{ $limitStatus['remaining']['monthly_count'] }} remaining</span>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Statistics Tabs -->
        <div class="card">
            <h3 class="text-yellow mb-4">📈 Transaction Statistics</h3>
            
            <div class="tabs">
                <div class="tab active" onclick="showTab('stats-7')">7 Days</div>
                <div class="tab" onclick="showTab('stats-30')">30 Days</div>
                <div class="tab" onclick="showTab('stats-90')">90 Days</div>
            </div>

            <!-- 7 Days Stats -->
            @if(isset($userStats7Days))
            <div id="stats-7" class="tab-content active">
                <div class="grid grid-3">
                    <div style="background: #0f172a; padding: 16px; border-radius: 8px; text-align: center;">
                        <div class="text-gray text-sm">Total Sent</div>
                        <div class="text-lg font-bold text-green">{{ number_format($userStats7Days['successful_amount']) }} XAF</div>
                    </div>
                    <div style="background: #0f172a; padding: 16px; border-radius: 8px; text-align: center;">
                        <div class="text-gray text-sm">Success Rate</div>
                        <div class="text-lg font-bold" style="color: {{ $userStats7Days['success_rate'] >= 90 ? '#10b981' : ($userStats7Days['success_rate'] >= 70 ? '#f59e0b' : '#ef4444') }};">{{ number_format($userStats7Days['success_rate'], 1) }}%</div>
                    </div>
                    <div style="background: #0f172a; padding: 16px; border-radius: 8px; text-align: center;">
                        <div class="text-gray text-sm">Transactions</div>
                        <div class="text-lg font-bold text-blue">{{ $userStats7Days['successful_count'] }}</div>
                    </div>
                </div>
            </div>
            @endif

            <!-- 30 Days Stats -->
            @if(isset($userStats30Days))
            <div id="stats-30" class="tab-content">
                <div class="grid grid-3">
                    <div style="background: #0f172a; padding: 16px; border-radius: 8px; text-align: center;">
                        <div class="text-gray text-sm">Total Sent</div>
                        <div class="text-lg font-bold text-green">{{ number_format($userStats30Days['successful_amount']) }} XAF</div>
                    </div>
                    <div style="background: #0f172a; padding: 16px; border-radius: 8px; text-align: center;">
                        <div class="text-gray text-sm">Success Rate</div>
                        <div class="text-lg font-bold" style="color: {{ $userStats30Days['success_rate'] >= 90 ? '#10b981' : ($userStats30Days['success_rate'] >= 70 ? '#f59e0b' : '#ef4444') }};">{{ number_format($userStats30Days['success_rate'], 1) }}%</div>
                    </div>
                    <div style="background: #0f172a; padding: 16px; border-radius: 8px; text-align: center;">
                        <div class="text-gray text-sm">Transactions</div>
                        <div class="text-lg font-bold text-blue">{{ $userStats30Days['successful_count'] }}</div>
                    </div>
                    <div style="background: #0f172a; padding: 16px; border-radius: 8px; text-align: center;">
                        <div class="text-gray text-sm">Active Days</div>
                        <div class="text-lg font-bold text-yellow">{{ $userStats30Days['active_days'] }}</div>
                    </div>
                    <div style="background: #0f172a; padding: 16px; border-radius: 8px; text-align: center;">
                        <div class="text-gray text-sm">Avg Transaction</div>
                        <div class="text-lg font-bold">{{ number_format($userStats30Days['average_transaction_amount']) }} XAF</div>
                    </div>
                    <div style="background: #0f172a; padding: 16px; border-radius: 8px; text-align: center;">
                        <div class="text-gray text-sm">Daily Average</div>
                        <div class="text-lg font-bold">{{ number_format($userStats30Days['average_daily_amount']) }} XAF</div>
                    </div>
                </div>
            </div>
            @endif

            <!-- 90 Days Stats -->
            @if(isset($userStats90Days))
            <div id="stats-90" class="tab-content">
                <div class="grid grid-3">
                    <div style="background: #0f172a; padding: 16px; border-radius: 8px; text-align: center;">
                        <div class="text-gray text-sm">Total Sent</div>
                        <div class="text-lg font-bold text-green">{{ number_format($userStats90Days['successful_amount']) }} XAF</div>
                    </div>
                    <div style="background: #0f172a; padding: 16px; border-radius: 8px; text-align: center;">
                        <div class="text-gray text-sm">Success Rate</div>
                        <div class="text-lg font-bold" style="color: {{ $userStats90Days['success_rate'] >= 90 ? '#10b981' : ($userStats90Days['success_rate'] >= 70 ? '#f59e0b' : '#ef4444') }};">{{ number_format($userStats90Days['success_rate'], 1) }}%</div>
                    </div>
                    <div style="background: #0f172a; padding: 16px; border-radius: 8px; text-align: center;">
                        <div class="text-gray text-sm">Transactions</div>
                        <div class="text-lg font-bold text-blue">{{ $userStats90Days['successful_count'] }}</div>
                    </div>
                    <div style="background: #0f172a; padding: 16px; border-radius: 8px; text-align: center;">
                        <div class="text-gray text-sm">Active Days</div>
                        <div class="text-lg font-bold text-yellow">{{ $userStats90Days['active_days'] }}</div>
                    </div>
                    <div style="background: #0f172a; padding: 16px; border-radius: 8px; text-align: center;">
                        <div class="text-gray text-sm">Avg Transaction</div>
                        <div class="text-lg font-bold">{{ number_format($userStats90Days['average_transaction_amount']) }} XAF</div>
                    </div>
                    <div style="background: #0f172a; padding: 16px; border-radius: 8px; text-align: center;">
                        <div class="text-gray text-sm">Daily Average</div>
                        <div class="text-lg font-bold">{{ number_format($userStats90Days['average_daily_amount']) }} XAF</div>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Limit Information -->
        <div class="card">
            <h3 class="mb-4">ℹ️ About Transaction Limits</h3>
            <div style="background: #0f172a; padding: 16px; border-radius: 8px; border-left: 4px solid #3b82f6;">
                <p class="mb-2"><strong>Daily Limits:</strong> Reset every day at midnight (UTC).</p>
                <p class="mb-2"><strong>Monthly Limits:</strong> Reset on the 1st of each month.</p>
                <p class="mb-2"><strong>Warning Threshold:</strong> You'll receive warnings when you reach 80% of any limit.</p>
                <p><strong>Need Higher Limits?</strong> Contact our support team to discuss increasing your transaction limits.</p>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabId) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabId).classList.add('active');
            
            // Add active class to clicked tab
            event.target.classList.add('active');
        }
    </script>
</body>
</html>
