"use client";
import React from "react";
import Link from "next/link";
import { useMutation, useQuery } from "@tanstack/react-query";
import RequireAuth from "@/components/guards/require-auth";
import { CardSkeleton } from "@/components/ui/skeleton";
import BankPicker, { Bank } from "@/components/banks/bank-picker";
import { addRecent, loadRecents } from "@/lib/recents";
import { loadBankDirectory, resolveBankName } from "@/lib/banks";
import http from "@/lib/api";
import { useRouter, useSearchParams } from "next/navigation";

// Reuse same Name Enquiry shape as transfer/verify
type NameEnquiryRes = {
  success?: boolean;
  accountName?: string;
  bankName?: string;
  reference?: string;
  message?: string;
};

export default function ProtectedVerifyPage() {
  return (
    <RequireAuth>
      <React.Suspense fallback={<div className="min-h-dvh grid place-items-center p-6 text-sm text-gray-600">Loading…</div>}>
        <ProtectedVerifyInner />
      </React.Suspense>
    </RequireAuth>
  );
}

function ProtectedVerifyInner() {
  const router = useRouter();
  const params = useSearchParams();

  const [bankCode, setBankCode] = React.useState(params.get("bankCode") || "");
  const [bankName, setBankName] = React.useState(params.get("bankName") || "");
  const [account, setAccount] = React.useState(params.get("account") || "");
  const [pickerOpen, setPickerOpen] = React.useState(false);
  const [ne, setNe] = React.useState<NameEnquiryRes | null>(null);
  const [error, setError] = React.useState<string | null>(null);
  const [suggestions, setSuggestions] = React.useState<Bank[]>([]);
  const [suggestBusy, setSuggestBusy] = React.useState(false);
  const [lastNEKey, setLastNEKey] = React.useState<string>("");
  const [verifiedKey, setVerifiedKey] = React.useState<string>("");
  const [toast, setToast] = React.useState<string>("");
  const [tab, setTab] = React.useState<'recents'|'favourites'>("recents");
  const [search, setSearch] = React.useState("");

  const nameEnquiry = useMutation({
    mutationFn: async () => {
      setError(null); setNe(null);
      const res = await http.post("/api/mobile/transfers/name-enquiry", { bankCode, accountNumber: account });
      return res.data as NameEnquiryRes;
    },
    onSuccess: (d) => {
      const keyNow = bankCode + ":" + account.trim();
      if (keyNow !== lastNEKey) return;
      setNe(d);
      if (d?.accountName) {
        try { if (d?.reference) sessionStorage.setItem(`ne:ref:${bankCode}:${account.trim()}`, d.reference); } catch {}
        addRecent({ bankCode, bankName, accountNumber: account, accountName: d.accountName });
        setVerifiedKey(keyNow);
        setToast(`Verified ${d.accountName}`);
        setTimeout(() => setToast(""), 2000);
      }
    },
    onError: (e: any) => {
      const msg = e?.response?.data?.message || e.message;
      setError(msg);
    },
  });

  // Suggest banks from account number
  React.useEffect(() => {
    const acct = account.trim();
    if (acct.length < 6) { setSuggestions([]); return; }
    let cancelled = false;
    (async () => {
      try {
        setSuggestBusy(true);
        const res = await http.post('/api/mobile/banks/suggest', { accountNumber: acct });
        const data = res.data || {};
        if (cancelled) return;
        const bank = data.bank as Bank | undefined;
        const list = (data.suggestions as Bank[] | undefined) || [];
        setSuggestions(list);
        if (data.resolved && bank?.bankCode) {
          setBankCode(bank.bankCode);
          setBankName(bank.name || "");
        }
      } catch {
        if (!cancelled) setSuggestions([]);
      } finally {
        if (!cancelled) setSuggestBusy(false);
      }
    })();
    return () => { cancelled = true; };
  }, [account]);

  // Prefetch bank directory and restore state once
  React.useEffect(() => { loadBankDirectory().catch(() => undefined); }, []);

  // Auto NE when ready
  React.useEffect(() => {
    const acct = account.trim();
    const ready = !!bankCode && acct.length >= 10;
    if (!ready) return;
    const key = bankCode + ":" + acct;
    if (key === lastNEKey) return;
    const id = setTimeout(() => { setLastNEKey(key); nameEnquiry.mutate(); }, 450);
    return () => clearTimeout(id);
  }, [bankCode, account, lastNEKey]);

  function goNext() {
    const key = bankCode + ":" + account.trim();
    if (!ne?.accountName || !bankCode || !account || key !== verifiedKey) return;
    const sp = new URLSearchParams({ bankCode, bankName, account, accountName: ne.accountName || "" });
    router.push(`/protected/amount?${sp.toString()}`);
  }

  // Recents from API + MRU
  const recents = useQuery<{ data: Array<{ bankCode: string; accountNumber: string; accountName?: string; bankName?: string }>}>({
    queryKey: ["protected-recent-recipients"],
    queryFn: async () => { const res = await http.get("/api/mobile/transfers", { params: { perPage: 20 } }); return res.data as any; },
    staleTime: 60_000,
  });

  return (
    <div className="min-h-dvh p-6 max-w-2xl mx-auto space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-2">
          <button onClick={() => router.back()} aria-label="Back" className="text-xl">‹</button>
          <h1 className="text-lg font-semibold">Start Texa Protect</h1>
        </div>
        <Link href="/protected/history" className="text-sm underline">History</Link>
      </div>

      {error ? <div className="text-sm text-red-600 border border-red-200 rounded p-2">{error}</div> : null}

      {/* Recipient Account */}
      <section className="border rounded-xl p-4 space-y-3">
        <div className="text-sm font-medium">Recipient Account</div>
        <div>
          <input
            className="w-full px-0 py-2 text-base border-b focus:outline-none focus:ring-0"
            value={account}
            onChange={(e) => { const only = e.target.value.replace(/\D+/g, '').slice(0, 10); setAccount(only); }}
            inputMode="numeric"
            pattern="[0-9]*"
            autoComplete="off"
            placeholder="Enter 10 digits Account Number"
          />
          <div className="text-xs text-gray-600 mt-1 flex items-center gap-2">
            <span>Enter 10 digits Account Number</span>
            {ne?.accountName && (bankCode+":"+account.trim())===verifiedKey && (
              <span className="inline-flex items-center gap-1 text-emerald-700 bg-emerald-50 border border-emerald-200 px-1.5 py-0.5 rounded">
                <span className="inline-block h-2 w-2 rounded-full bg-emerald-600" />
                Verified
              </span>
            )}
          </div>
          {suggestBusy && <div className="text-xs text-gray-500 mt-1">Checking bank suggestions…</div>}
        </div>
        {/* Bank row */}
        <div>
          <button type="button" className="w-full px-0 py-3 flex items-center justify-between hover:bg-gray-50 rounded" onClick={() => setPickerOpen(true)} aria-label="Select Bank">
            <span className="flex items-center gap-2">
              <span className="h-7 w-7 rounded-full bg-gray-100 flex items-center justify-center text-xs">{(bankName || '•').slice(0,1).toUpperCase()}</span>
              <span className="text-sm">{account.trim().length === 0 ? 'Select Bank' : (bankName || 'Select Bank')}</span>
            </span>
            <span aria-hidden className="text-gray-400">›</span>
          </button>
          {suggestions.length > 0 && (
            <div className="mt-2 flex flex-wrap gap-2">
              {suggestions.slice(0, 6).map((b) => (
                <button key={b.bankCode} className="text-xs border rounded px-2 py-1" onClick={() => { setBankCode(b.bankCode); setBankName(b.name); }}>{b.name}</button>
              ))}
            </div>
          )}
          {/* Inline verification status */}
          <div role="status" aria-live="polite" className="mt-3">
            {nameEnquiry.isPending && (
              <span className="inline-flex items-center gap-2 text-xs px-2 py-1 rounded bg-gray-100 text-gray-800">
                <svg className="animate-spin h-3.5 w-3.5" viewBox="0 0 24 24"><circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle><path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                Verifying…
              </span>
            )}
            {!nameEnquiry.isPending && ne?.accountName && (
              <span className="inline-flex items-center gap-2 text-sm px-2 py-1 rounded bg-emerald-50 text-emerald-800 border border-emerald-200">
                <span className="inline-block h-2 w-2 rounded-full bg-emerald-600" />
                {ne.accountName}
              </span>
            )}
            {!nameEnquiry.isPending && !ne?.accountName && !!bankCode && account.trim().length>=10 && (
              <button type="button" className="ml-2 text-xs underline text-gray-700" onClick={() => { setLastNEKey(bankCode+":"+account.trim()); nameEnquiry.mutate(); }}>
                Verify now
              </button>
            )}
          </div>
        </div>
      </section>

      {/* Recents */}
      <section className="border rounded-xl p-4">
        <div className="flex items-center justify-between mb-3">
          <div className="flex items-center gap-4 text-sm">
            <button className={`pb-1 ${tab==='recents'?'border-b-2 border-black font-medium':''}`} onClick={() => setTab('recents')}>Recents</button>
          </div>
          <input className="border rounded px-2 py-1 text-sm" placeholder="Search" value={search} onChange={(e)=>setSearch(e.target.value)} />
        </div>
        {recents.isLoading ? <CardSkeleton lines={2} /> : (
          <div className="divide-y">
            {(() => {
              const fromApi = (recents.data?.data || []).map(r => ({ bankCode: r.bankCode, bankName: resolveBankName(r.bankCode, r.bankName), accountNumber: r.accountNumber, accountName: r.accountName }));
              const mergedMap = new Map<string, { bankCode: string; bankName?: string; accountNumber: string; accountName?: string }>();
              const keyf = (x: any) => `${x.bankCode}:${x.accountNumber}`;
              for (const it of loadRecents()) mergedMap.set(keyf(it), it as any);
              for (const it of fromApi) mergedMap.set(keyf(it), it);
              const list = Array.from(mergedMap.values()).filter(r => {
                const q = search.trim().toLowerCase();
                if (!q) return true;
                return (r.accountName||'').toLowerCase().includes(q) || (r.bankName||'').toLowerCase().includes(q) || r.accountNumber.includes(q);
              }).slice(0,20);
              return list.length ? list.map((r) => (
                <button key={r.bankCode+":"+r.accountNumber} className="w-full flex items-center justify-between p-3 hover:bg-gray-50 text-left" onClick={() => {
                  setBankCode(r.bankCode); setBankName(r.bankName || ""); setAccount(r.accountNumber);
                  if (r.accountName) {
                    setNe({ accountName: r.accountName, bankName: r.bankName, success: true });
                    const k = r.bankCode+":"+r.accountNumber; setVerifiedKey(k); setToast(`Verified ${r.accountName}`); setTimeout(() => setToast(""), 2000);
                    setTimeout(() => nameEnquiry.mutate(), 0);
                  }
                }}>
                  <div className="text-sm flex items-center justify-between w-full">
                    <div>
                      <div className="font-medium line-clamp-1">{r.accountName || r.accountNumber}</div>
                      <div className="text-xs text-gray-600">{(r.accountNumber||'').slice(-4)} · {resolveBankName(r.bankCode)}</div>
                    </div>
                    <div className="flex items-center gap-2">
                      <div className="h-7 w-7 rounded-full bg-gray-100 flex items-center justify-center text-[11px]">{(resolveBankName(r.bankCode) || '').slice(0,1).toUpperCase()}</div>
                      <div className="text-xs text-gray-500">›</div>
                    </div>
                  </div>
                </button>
              )) : <div className="p-3 text-sm text-gray-600">No recipients.</div>;
            })()}
          </div>
        )}
      </section>

      <button className="w-full h-12 rounded-full bg-emerald-600 text-white font-medium disabled:opacity-50 disabled:cursor-not-allowed hover:bg-emerald-700 transition-colors" onClick={goNext} disabled={!ne?.accountName || (bankCode+":"+account.trim()) !== verifiedKey}>Next</button>

      {toast && <div className="fixed bottom-20 left-1/2 -translate-x-1/2 bg-black text-white text-sm px-3 py-1.5 rounded">{toast}</div>}
      <BankPicker open={pickerOpen} onClose={() => setPickerOpen(false)} onSelect={(b) => { setBankCode(b.bankCode); setBankName(b.name); }} />
    </div>
  );
}
