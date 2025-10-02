@extends('layouts.app')

@section('content')
    <style>
        body { font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif; background: #0b1020; color: #e6e8ec; margin: 0; }
        .container { max-width: 640px; margin: 40px auto; padding: 24px; background: #121836; border-radius: 14px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        h1 { margin: 0 0 8px; font-size: 22px; }
        p.muted { color: #9aa3b2; margin-top: 4px; }
        form { margin-top: 16px; display: grid; gap: 12px; }
        label { font-size: 13px; color: #c9d4e5; }
        input { width: 100%; padding: 12px 12px; background: #0e1430; color: #e6e8ec; border: 1px solid #1c2347; border-radius: 10px; font-size: 14px; }
        .row { display: grid; grid-template-columns: 1fr; gap: 12px; }
        .btn { background: #f59e0b; color: #1a1b2e; padding: 12px 14px; border-radius: 10px; font-weight: 600; border: none; cursor: pointer; }
        .btn:hover { background: #f7b23a; }
        .card { background: #0e1430; border: 1px solid #1c2347; border-radius: 12px; padding: 16px; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .divider { border:0;border-top:1px solid #1c2347;margin:18px 0; }
        .alert { padding: 10px 12px; border-radius: 10px; font-size: 14px; }
        .alert-error { background: #2c1430; color: #ffd6e7; border: 1px solid #5d264d; }
        .alert-info { background: #102a3a; color: #b8ecff; border: 1px solid #1c4e63; }
        .kv { display: flex; justify-content: space-between; margin: 6px 0; color: #c9d4e5; }
        .kv strong { color: #e6e8ec; }
        .countdown { font-weight: 600; color: #f59e0b; }
        .live { font-size: 14px; color:#cbd5e1; margin-top:8px; }
        .live .pill { display:inline-block;background:#19324d;color:#b8ecff;border:1px solid #28557a;padding:4px 8px;border-radius:999px;font-weight:600;margin-right:8px;}
        .live .row { display:flex; align-items:center; gap:10px; }
        .live .err { color:#fecaca; }
    </style>
<div class="container">
    <h1>Quote • XAF → NGN</h1>
    <p class="muted">Recipient: <strong>{{ $accountName }}</strong> • {{ $bankName }} • {{ $accountNumber }}<?php
    // Intentionally left blank
    ?></p>

    @if(session('error'))
        <div class="alert alert-error">{{ session('error') }}</div>
    @endif

    <!-- Limit Warnings -->
    @if(!empty($limitWarnings))
        @foreach($limitWarnings as $warning)
            <div style="background:{{ $warning['level'] === 'critical' ? '#dc2626' : '#f59e0b' }};color:#fff;padding:12px;border-radius:8px;margin-bottom:16px;">
                <strong>{{ $warning['level'] === 'critical' ? '⚠️ Critical' : '⚠️ Warning' }}:</strong> {{ $warning['message'] }}
            </div>
        @endforeach
    @endif

    <!-- Quick Limit Status -->
    @if(isset($limitStatus['limits']))
    <div style="background:#1e293b;border-radius:8px;padding:16px;margin-bottom:16px;border:1px solid #334155;">
        <h4 style="margin:0 0 12px 0;color:#f8fafc;">📊 Today's Usage</h4>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;font-size:14px;">
            <div>
                <div style="color:#cbd5e1;">Daily Amount</div>
                <div style="color:#f8fafc;font-weight:600;">{{ number_format($limitStatus['usage']['daily_amount']) }} / {{ number_format($limitStatus['limits']['daily_limit_xaf']) }} XAF</div>
                <div style="background:#374151;height:4px;border-radius:2px;overflow:hidden;margin-top:4px;">
                    <div style="background:{{ $limitStatus['utilization']['daily_percentage'] >= 80 ? '#ef4444' : '#10b981' }};height:100%;width:{{ min(100, $limitStatus['utilization']['daily_percentage']) }}%;"></div>
                </div>
            </div>
            <div>
                <div style="color:#cbd5e1;">Daily Transactions</div>
                <div style="color:#f8fafc;font-weight:600;">{{ $limitStatus['usage']['daily_count'] }} / {{ $limitStatus['limits']['daily_count_limit'] }}</div>
                <div style="background:#374151;height:4px;border-radius:2px;overflow:hidden;margin-top:4px;">
                    <div style="background:{{ $limitStatus['utilization']['daily_count_percentage'] >= 80 ? '#ef4444' : '#10b981' }};height:100%;width:{{ min(100, $limitStatus['utilization']['daily_count_percentage']) }}%;"></div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="card">
        <form method="post" action="{{ route('transfer.quote.create') }}">
            @csrf
            <label for="amount_xaf">Amount to send (XAF)</label>
            <input type="number" id="amount_xaf" name="amount_xaf" min="1" step="1" value="{{ old('amount_xaf') }}" placeholder="e.g. 100000" required />
            <div id="livePreview" class="live" aria-live="polite">
                <div id="liveRow" class="row" style="display:none;">
                    <span id="ratePill" class="pill">1 XAF ≈ — NGN</span>
                    <span>Recipient gets: <strong id="recvNgn">—</strong> NGN</span>
                    <span>Fees: <strong id="feesXaf">—</strong> XAF</span>
                    <span>You pay: <strong id="payXaf">—</strong> XAF</span>
                </div>
                <div id="liveLoading" style="display:none;">Calculating…</div>
                <div id="liveError" class="err" style="display:none;">Rate unavailable — please try again.</div>
            </div>
            <button class="btn" type="submit">Get Quote</button>
        </form>
    </div>

    @if(!empty($quote))
        <hr class="divider" />
        <div class="card">
            <div class="grid-2">
                <div>
                    <div class="kv"><span>FX (XAF→NGN)</span><strong>{{ number_format($quote->adjusted_rate_xaf_to_ngn, 6) }}</strong></div>
                    <div class="kv"><span>Fees (XAF)</span><strong>{{ number_format($quote->fee_total_xaf) }}</strong></div>
                    <div class="kv"><span>You pay (XAF)</span><strong>{{ number_format($quote->total_pay_xaf) }}</strong></div>
                    <div class="kv"><span>Recipient gets (NGN)</span><strong>{{ number_format($quote->receive_ngn_minor / 100, 2) }}</strong></div>
                </div>
                <div>
                    <div class="alert alert-info">
                        Quote lock: <span class="countdown" id="countdown">{{ $remaining ?? 0 }}</span> seconds
                    </div>
                    <form method="post" action="{{ route('transfer.confirm') }}">
                        @csrf
                        <label for="msisdn">Your MoMo Number (MSISDN)</label>
                        <input type="text" id="msisdn" name="msisdn" placeholder="e.g. 677XXXXXX" required />
                        <button class="btn" id="confirmBtn" type="submit" {{ ($remaining ?? 0) <= 0 ? 'disabled' : '' }}>Confirm & Pay</button>
                    </form>
                    @if(($remaining ?? 0) <= 0)
                        <p class="muted" style="margin-top:8px;">Quote expired — please refresh by entering the amount again.</p>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
<script>
(function(){
    var remaining = {{ (int) ($remaining ?? 0) }};
    var el = document.getElementById('countdown');
    var btn = document.getElementById('confirmBtn');
    var refreshTimeout = null;
    var amountEl = document.getElementById('amount_xaf');
    var routePreview = "{{ route('api.pricing.preview') }}";
    var liveRow = document.getElementById('liveRow');
    var ratePill = document.getElementById('ratePill');
    var recvNgn = document.getElementById('recvNgn');
    var feesXaf = document.getElementById('feesXaf');
    var payXaf = document.getElementById('payXaf');
    var liveLoading = document.getElementById('liveLoading');
    var liveError = document.getElementById('liveError');
    
    if (!el) return;
    
    var updateCountdown = function() {
        if (remaining <= 0) {
            clearInterval(t);
            if (el) el.textContent = '0';
            if (btn) btn.disabled = true;
            
            // Schedule page refresh after showing expiration for 2 seconds
            if (!refreshTimeout) {
                refreshTimeout = setTimeout(function() {
                    window.location.reload();
                }, 2000);
            }
            return;
        }
        
        remaining -= 1;
        if (el) el.textContent = remaining;
        if (remaining <= 0 && btn) { 
            btn.disabled = true; 
        }
    };
    
    // Initial update
    updateCountdown();
    
    // Update every second
    var t = setInterval(updateCountdown, 1000);
    
    // Also check for server-side expiration
    var checkExpiration = function() {
        fetch(window.location.href, {
            method: 'HEAD',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        }).then(function(response) {
            if (response.redirected) {
                window.location.reload();
            }
        });
    };

    // Prevent multiple form submissions
    var form = document.querySelector('form[action="{{ route('transfer.confirm') }}"]');
    var isSubmitting = false;
    
    if (form) {
        form.addEventListener('submit', function(e) {
            if (isSubmitting) {
                e.preventDefault();
                return false;
            }
            
            isSubmitting = true;
            var submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Processing...';
            }
            
            // Re-enable after 10 seconds as fallback
            setTimeout(function() {
                isSubmitting = false;
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Confirm & Pay';
                }
            }, 10000);
        });
    }
    
    // Check expiration every 10 seconds
    var expirationCheck = setInterval(checkExpiration, 10000);

    // ---- Live pricing preview (debounced) ----
    function formatNumber(n, frac) {
        var x = Number(n);
        if (!isFinite(x)) return '—';
        return x.toLocaleString(undefined, { minimumFractionDigits: frac, maximumFractionDigits: frac });
    }

    function renderPreview(data) {
        liveLoading.style.display = 'none';
        liveError.style.display = 'none';
        if (!data) { liveRow.style.display = 'none'; return; }
        liveRow.style.display = '';
        ratePill.textContent = '1 XAF ≈ ' + formatNumber(data.adjustedRate, 6) + ' NGN';
        recvNgn.textContent = formatNumber((data.receiveNgnMinor || 0) / 100, 2);
        feesXaf.textContent = formatNumber(data.feeTotalXaf || 0, 0);
        payXaf.textContent = formatNumber(data.totalPayXaf || 0, 0);
    }

    var liveTimer = null;
    function fetchPreviewDebounced() {
        if (!amountEl) return;
        var val = amountEl.value ? parseInt(amountEl.value, 10) : 0;
        if (liveTimer) { clearTimeout(liveTimer); }
        liveTimer = setTimeout(function() {
            if (!val || val < 1) { renderPreview(null); return; }
            liveLoading.style.display = '';
            liveError.style.display = 'none';
            fetch(routePreview + '?amountXaf=' + encodeURIComponent(val), {
                method: 'GET',
                headers: { 'Accept': 'application/json' }
            })
            .then(function(r){ if(!r.ok) throw new Error('bad'); return r.json(); })
            .then(function(json){
                // preview endpoint returns fields directly (not wrapped)
                if (json && typeof json.adjustedRate !== 'undefined') {
                    renderPreview(json);
                } else if (json && json.amountXaf) {
                    renderPreview(json); // same keys
                } else if (json && json.success && json.quote) {
                    // not expected here, but guard
                    renderPreview({
                        adjustedRate: json.quote.adjustedRate,
                        receiveNgnMinor: json.quote.receiveNgnMinor,
                        feeTotalXaf: json.quote.feeTotalXaf,
                        totalPayXaf: json.quote.totalPayXaf
                    });
                } else {
                    throw new Error('shape');
                }
            })
            .catch(function(){
                liveLoading.style.display = 'none';
                liveRow.style.display = 'none';
                liveError.style.display = '';
            });
        }, 400);
    }

    if (amountEl) {
        amountEl.addEventListener('input', fetchPreviewDebounced);
        // Fire once on load if there is a value
        if (amountEl.value) { fetchPreviewDebounced(); }
    }
})();
</script>
@endsection
