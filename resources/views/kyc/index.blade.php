@extends('layouts.app')

@section('content')
<div class="container mx-auto max-w-2xl py-8">
    <h1 class="text-2xl font-semibold mb-4">Verify your identity</h1>
    <p class="mb-4">Verifying your identity unlocks higher sending limits. This takes about 1–2 minutes.</p>

    <div class="rounded border p-4 mb-6">
        <div class="mb-2"><strong>Status:</strong>
            <span id="kyc-status" class="inline-block px-2 py-1 rounded bg-gray-100">
                {{ $user->kyc_status ?? 'unverified' }} (Level {{ (int)($user->kyc_level ?? 0) }})
            </span>
        </div>
        @if($user->kyc_verified_at)
            <div class="text-sm text-gray-600">Verified at: {{ $user->kyc_verified_at->diffForHumans() }}</div>
        @endif
    </div>

    <div class="flex gap-3">
        <button id="start-kyc" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">Start KYC</button>
        <a href="{{ route('transfer.bank') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-900 px-4 py-2 rounded">Back</a>
    </div>

    <pre id="kyc-log" class="mt-6 p-4 bg-gray-50 rounded text-xs overflow-x-auto hidden"></pre>
</div>
@endsection

@push('scripts')
<script>
(function(){
    const btn = document.getElementById('start-kyc');
    const log = document.getElementById('kyc-log');
    const statusEl = document.getElementById('kyc-status');
    let pollTimer = null;

    function writeLog(obj){
        log.classList.remove('hidden');
        log.textContent = JSON.stringify(obj, null, 2);
    }

    async function pollStatusOnce(){
        try {
            const r = await fetch('{{ route('kyc.status') }}', { headers: { 'Accept':'application/json' } });
            if (!r.ok) return;
            const s = await r.json();
            if (s?.kyc_status === 'verified') {
                statusEl.textContent = 'verified (Level ' + (s?.kyc_level ?? 1) + ')';
                if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
            } else if (s?.kyc_status) {
                statusEl.textContent = s.kyc_status + ' (Level ' + (s?.kyc_level ?? 0) + ')';
            }
        } catch (_) { /* ignore one-off errors during polling */ }
    }

    function startPolling(){
        if (pollTimer) return;
        pollTimer = setInterval(pollStatusOnce, 5000);
    }

    btn?.addEventListener('click', async function(){
        btn.disabled = true;
        statusEl.textContent = 'pending (starting...)';
        try {
            // Try new web-token endpoint first
            const tokenResp = await fetch('{{ route('kyc.smileid.web_token') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            });
            const tokenData = await tokenResp.json();
            writeLog(tokenData);

            if (tokenData && tokenData.enabled && tokenData.token) {
                statusEl.textContent = 'pending (opening Smile ID)';
                if (window.SmileIdentity) {
                    try {
                        window.SmileIdentity({
                            token: tokenData.token,
                            product: 'doc_verification',
                            environment: 'sandbox', // set to 'live' in production
                            callback_url: '{{ route('kyc.smileid.callback') }}',
                            partner_details: {
                                partner_id: '{{ env('SMILE_ID_PARTNER_ID') }}',
                                name: '{{ config('app.name') }}',
                                logo_url: '{{ asset('images/logo.png') }}',
                                policy_url: '{{ url('/privacy') }}',
                                theme_color: '#1E3A8A'
                            },
                            // Cameroon configuration (adjust id types as enabled on your account)
                            id_selection: { CM: ['NATIONAL_ID', 'PASSPORT'] },
                            consent_required: { CM: ['NATIONAL_ID', 'PASSPORT'] },
                            document_capture_modes: ['camera','upload'],
                            onSuccess: () => { startPolling(); },
                            onClose: () => { startPolling(); },
                            onError: () => { startPolling(); }
                        });
                    } catch (e) {
                        console.error('SmileIdentity init error', e);
                        startPolling();
                    }
                } else {
                    alert('Smile ID Web SDK not loaded. We will continue polling your verification status.');
                    startPolling();
                }
                return;
            }

            // Fallback to legacy start-session flow
            const resp = await fetch('{{ route('kyc.smileid.start') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            });
            const data = await resp.json();
            writeLog(data);
            if (data && data.enabled) {
                statusEl.textContent = 'pending (submit via Smile ID)';
                const sdk = window.SmileIdentity || window.SmileId || null;
                if (sdk && typeof sdk.startVerification === 'function') {
                    try {
                        await sdk.startVerification(data.session);
                        startPolling();
                    } catch (sdkErr) {
                        console.error('Smile ID SDK error:', sdkErr);
                        alert('Smile ID could not start or was cancelled. You can retry.');
                    }
                } else {
                    alert('KYC session started. SDK not detected on page; showing session payload. We\'ll poll your status every 5s after submission.');
                    startPolling();
                }
            } else {
                statusEl.textContent = 'unverified (KYC disabled)';
            }
        } catch (e) {
            writeLog({ error: e?.message || String(e) });
            statusEl.textContent = 'unverified (error)';
        } finally {
            btn.disabled = false;
        }
    });
})();
</script>
@endpush
