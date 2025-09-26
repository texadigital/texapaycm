<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Transfer to Bank (Nigeria)</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <style>
        body { font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif; background: #0b1020; color: #e6e8ec; margin: 0; }
        .container { max-width: 560px; margin: 40px auto; padding: 24px; background: #121836; border-radius: 14px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        h1 { margin: 0 0 16px; font-size: 22px; }
        p.muted { color: #9aa3b2; margin-top: 4px; }
        form { margin-top: 20px; display: grid; gap: 14px; }
        label { font-size: 13px; color: #c9d4e5; }
        input, select { width: 100%; padding: 12px 12px; background: #0e1430; color: #e6e8ec; border: 1px solid #1c2347; border-radius: 10px; font-size: 14px; }
        .row { display: grid; grid-template-columns: 1fr; gap: 12px; }
        .btn { background: #f59e0b; color: #1a1b2e; padding: 12px 14px; border-radius: 10px; font-weight: 600; border: none; cursor: pointer; }
        .btn:hover { background: #f7b23a; }
        .alert { padding: 10px 12px; border-radius: 10px; font-size: 14px; }
        .alert-error { background: #2c1430; color: #ffd6e7; border: 1px solid #5d264d; }

        /* Bank selector button */
        .selector { display: flex; align-items: center; justify-content: space-between; padding: 12px; background: #0e1430; border: 1px solid #1c2347; border-radius: 10px; cursor: pointer; }
        .selector .label { color: #9aa3b2; font-size: 12px; }
        .selector .value { font-weight: 600; }
        .hint { font-size: 12px; color: #9aa3b2; }
        .chip { display: inline-block; padding: 6px 10px; border-radius: 999px; background: #0e1430; border: 1px solid #1c2347; margin: 4px 6px 0 0; cursor: pointer; font-size: 12px; }

        /* Bottom sheet */
        .sheet-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: none; }
        .sheet { position: fixed; left: 0; right: 0; bottom: -80%; background: #0e1430; border-top-left-radius: 16px; border-top-right-radius: 16px; padding: 14px; max-height: 75vh; overflow: auto; box-shadow: 0 -10px 30px rgba(0,0,0,0.4); transition: bottom 0.25s ease; }
        .sheet.open { bottom: 0; }
        .sheet .drag { width: 44px; height: 5px; background: #253057; border-radius: 4px; margin: 6px auto 12px; }
        .sheet h3 { margin: 6px 0 10px; font-size: 16px; }
        .search { width: 100%; padding: 10px; background: #0b112b; color: #e6e8ec; border: 1px solid #1c2347; border-radius: 10px; }
        .bank-list { margin-top: 10px; }
        .bank-item { padding: 10px; border-bottom: 1px solid #1c2347; cursor: pointer; }
        .bank-item:hover { background: #0b112b; }
        .section-title { margin-top: 10px; color: #9aa3b2; font-size: 12px; }
    </style>
</head>
<body>
<div class="container">
    <h1>Transfer to Bank (Nigeria)</h1>
    <p class="muted">No XAF cash-out. Enter recipient bank and account number, then verify the name.</p>

    @if(session('transfer.error'))
        <div class="alert alert-error">{{ session('transfer.error') }}</div>
    @endif

    <form method="post" action="{{ route('transfer.bank.verify') }}">
        @csrf
        <div class="row">
            <div>
                <label for="account_number">Account Number</label>
                <input type="text" id="account_number" name="account_number" placeholder="10-digit NGN account" value="{{ old('account_number', $account_number) }}" required />
                <div class="hint" id="account_hint">Enter 10-digit account to auto-suggest banks.</div>
            </div>
            <div>
                <label>Bank</label>
                <div class="selector" id="bank_selector">
                    <div>
                        <div class="label">Selected Bank</div>
                        <div class="value" id="bank_selected_value">None</div>
                    </div>
                    <div>▼</div>
                </div>
                <input type="hidden" id="bank_code" name="bank_code" value="{{ old('bank_code', $bank_code) }}" />
                <div id="suggestions" class="hint" style="margin-top:6px;"></div>
            </div>
        </div>
        <button class="btn" type="submit">Verify Account Name</button>
    </form>

    @if($account_name)
        <hr style="border:0;border-top:1px solid #1c2347;margin:18px 0;" />
        <p><strong>Verified Name:</strong> {{ $account_name }}<br />
           <strong>Bank:</strong> {{ $bank_name }}<br />
           <strong>Account:</strong> {{ $account_number }}</p>
        <form method="get" action="{{ route('transfer.quote') }}">
            <button class="btn" type="submit">Continue to Quote</button>
        </form>
    @endif
</div>

<!-- Bottom sheet bank picker -->
<div class="sheet-backdrop" id="sheet_backdrop" aria-hidden="true"></div>
<div class="sheet" id="bank_sheet" role="dialog" aria-modal="true" aria-labelledby="sheet_title">
    <div class="drag"></div>
    <h3 id="sheet_title">Select Bank</h3>
    <input type="text" class="search" id="bank_search" placeholder="Search Bank Name" />
    <div class="section-title">Frequently Used</div>
    <div id="bank_recent" class="bank-list"></div>
    <div class="section-title">All Banks</div>
    <div id="bank_all" class="bank-list"></div>
    <div style="height:10px"></div>
    <button class="btn" type="button" id="sheet_close" style="width:100%;margin-top:8px;">Close</button>
    <div style="height:10px"></div>
    <div class="hint">Tip: Type to search; tap a bank to select.</div>
    <div style="height:14px"></div>
    <div id="sheet_status" class="hint"></div>
    <div style="height:8px"></div>
</div>

<script>
(function(){
  const $ = (s) => document.querySelector(s);
  const $$ = (s) => Array.from(document.querySelectorAll(s));
  const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  const suggestUrl = '/api/banks/suggest';
  const banksUrl = '/api/banks';
  const favoritesUrl = '/api/banks/favorites';

  const accountInput = $('#account_number');
  const hint = $('#account_hint');
  const suggestions = $('#suggestions');
  const hiddenBankCode = $('#bank_code');
  const bankValue = $('#bank_selected_value');
  const bankSelector = $('#bank_selector');

  const sheet = $('#bank_sheet');
  const backdrop = $('#sheet_backdrop');
  const search = $('#bank_search');
  const listAll = $('#bank_all');
  const listRecent = $('#bank_recent');
  const sheetStatus = $('#sheet_status');

  let banks = [];
  let recent = [];
  let banksLoaded = false;

  function openSheet(){
    backdrop.style.display = 'block';
    requestAnimationFrame(()=>{ sheet.classList.add('open'); });
  }
  function closeSheet(){
    sheet.classList.remove('open');
    backdrop.style.display = 'none';
  }
  $('#sheet_close').addEventListener('click', closeSheet);
  backdrop.addEventListener('click', closeSheet);
  bankSelector.addEventListener('click', async ()=>{
    await ensureBanks();
    renderBanks(banks);
    renderRecent(recent);
    openSheet();
    search.focus();
  });

  function setSelected(bank){
    if(!bank) return;
    hiddenBankCode.value = bank.bankCode || '';
    bankValue.textContent = bank.name || bank.bankCode || 'None';
    // Show as chip in suggestions area
    suggestions.innerHTML = '';
    closeSheet();
  }

  function bankItemHTML(b){
    const code = b.bankCode || '';
    const name = b.name || code;
    return `<div class="bank-item" data-code="${code}" data-name="${name}">${name}${code?` <span class=\"hint\">(${code})</span>`:''}</div>`;
  }
  function renderBanks(list){
    listAll.innerHTML = list.map(bankItemHTML).join('');
    listAll.querySelectorAll('.bank-item').forEach(el=>{
      el.addEventListener('click', ()=>{
        setSelected({ bankCode: el.dataset.code, name: el.dataset.name });
      });
    });
  }
  function renderRecent(list){
    if(!list || list.length===0){ listRecent.innerHTML = '<div class="hint">No recent banks yet.</div>'; return; }
    listRecent.innerHTML = list.map(bankItemHTML).join('');
    listRecent.querySelectorAll('.bank-item').forEach(el=>{
      el.addEventListener('click', ()=>{
        setSelected({ bankCode: el.dataset.code, name: el.dataset.name });
      });
    });
  }

  async function ensureBanks(){
    if(banksLoaded){ return; }
    // Try localStorage cache
    const cached = localStorage.getItem('banks_cache');
    if(cached){
      try {
        const obj = JSON.parse(cached);
        if(obj && Array.isArray(obj.banks) && (Date.now() - obj.time) < 24*60*60*1000){
          banks = obj.banks; banksLoaded = true;
        }
      } catch(_){}
    }
    if(!banksLoaded){
      const res = await fetch(banksUrl);
      const json = await res.json();
      banks = json.banks || [];
      localStorage.setItem('banks_cache', JSON.stringify({ time: Date.now(), banks }));
      banksLoaded = true;
    }
    // Load favorites (best-effort)
    try {
      const r = await fetch(favoritesUrl);
      const j = await r.json();
      recent = j.banks || [];
    } catch(_){ recent = []; }
  }

  // Search filter
  search.addEventListener('input', ()=>{
    const q = search.value.trim().toLowerCase();
    if(!q){ renderBanks(banks); return; }
    const filtered = banks.filter(b=> (b.name||'').toLowerCase().includes(q) || (b.aliases||[]).join(' ').toLowerCase().includes(q) || (b.bankCode||'').includes(q));
    renderBanks(filtered);
  });

  // Debounce helper
  function debounce(fn, ms){ let t; return function(...args){ clearTimeout(t); t = setTimeout(()=>fn.apply(this,args), ms); }; }

  const onAccountChange = debounce(async function(){
    const acct = accountInput.value.replace(/\D+/g,'');
    if(acct.length !== 10){
      hint.textContent = 'Enter 10-digit account to auto-suggest banks.';
      suggestions.innerHTML = '';
      return;
    }
    hint.textContent = 'Looking up banks…';
    try {
      const res = await fetch(suggestUrl, { method:'POST', headers:{ 'Content-Type':'application/json','X-CSRF-TOKEN': csrf }, body: JSON.stringify({ accountNumber: acct }) });
      const json = await res.json();
      if(json.resolved){
        setSelected(json.bank);
        suggestions.innerHTML = `<span class="chip">${json.bank.name} (${json.bank.bankCode})</span> <span class="hint">Resolved account name will appear after Verify.</span>`;
        hint.textContent = 'Bank resolved from account. You can change it if needed.';
      } else {
        hint.textContent = 'Select your bank from suggestions or open the list.';
        if(Array.isArray(json.suggestions)){
          suggestions.innerHTML = json.suggestions.slice(0,10).map(b=>`<span class="chip" data-code="${b.bankCode}" data-name="${b.name}">${b.name}</span>`).join('');
          suggestions.querySelectorAll('.chip').forEach(el=>{
            el.addEventListener('click', ()=>{
              setSelected({ bankCode: el.dataset.code, name: el.dataset.name });
            });
          });
        }
      }
    } catch (e){
      hint.textContent = 'Could not auto-suggest now. Please select bank.';
    }
  }, 450);

  accountInput.addEventListener('input', onAccountChange);

})();
</script>
</body>
</html>
