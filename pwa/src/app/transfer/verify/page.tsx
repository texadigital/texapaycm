"use client";
import React from "react";
import { useMutation, useQuery } from "@tanstack/react-query";
import { addRecent, loadRecents } from "@/lib/recents";
import { isUnauthorizedErr } from "@/lib/errors";
import http from "@/lib/api";
import RequireAuth from "@/components/guards/require-auth";
import { CardSkeleton } from "@/components/ui/skeleton";
import BankPicker, { Bank } from "@/components/banks/bank-picker";
import { useRouter, useSearchParams } from "next/navigation";

type NameEnquiryRes = {
  success?: boolean;
  accountName?: string;
  bankName?: string;
  reference?: string;
  message?: string;
};

export default function VerifyRecipientPage() {
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
  const [unauthorized, setUnauthorized] = React.useState(false);
  const [lastNEKey, setLastNEKey] = React.useState<string>("");
  const [mounted, setMounted] = React.useState(false);

  const nameEnquiry = useMutation({
    mutationFn: async () => {
      setError(null);
      setNe(null);
      const res = await http.post("/api/mobile/transfers/name-enquiry", { bankCode, accountNumber: account });
      return res.data as NameEnquiryRes;
    },
    onSuccess: (d) => {
      setNe(d);
      if (d?.accountName) {
        addRecent({ bankCode, bankName, accountNumber: account, accountName: d.accountName });
      }
    },
    onError: (e: any) => {
      const msg = e?.response?.data?.message || e.message;
      setError(msg);
      if (isUnauthorizedErr(e)) setUnauthorized(true);
    },
  });

  // Recent recipients via last transfers
  const recents = useQuery<{ data: Array<{ bankCode: string; accountNumber: string; accountName?: string; bankName?: string }>}>({
    queryKey: ["recent-recipients"],
    queryFn: async () => {
      const res = await http.get("/api/mobile/transfers", { params: { perPage: 20 } });
      return res.data as any;
    },
    staleTime: 60_000,
  });

  React.useEffect(() => {
    const acct = account.trim();
    if (acct.length < 6) { setSuggestions([]); return; }
    let cancelled = false;
    async function runSuggest() {
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
      } catch (e:any) {
        if (!cancelled) setSuggestions([]);
        if (isUnauthorizedErr(e)) setUnauthorized(true);
      } finally {
        if (!cancelled) setSuggestBusy(false);
      }
    }
    runSuggest();
    return () => { cancelled = true; };
  }, [account]);

  // Auto name-enquiry when bank selected and account length looks valid (>=10)
  React.useEffect(() => {
    const acct = account.trim();
    const ready = !!bankCode && acct.length >= 10;
    if (!ready) return;
    const key = bankCode + ":" + acct;
    if (key === lastNEKey) return; // avoid duplicate
    const id = setTimeout(() => {
      setLastNEKey(key);
      nameEnquiry.mutate();
    }, 800); // stronger debounce to avoid rate limits
    return () => clearTimeout(id);
  }, [bankCode, account, lastNEKey]);

  // Listen for session expiry broadcast and surface banner
  React.useEffect(() => {
    const onUnauthorized = () => setUnauthorized(true);
    window.addEventListener('auth:unauthorized', onUnauthorized as any);
    return () => window.removeEventListener('auth:unauthorized', onUnauthorized as any);
  }, []);

  // Persist/restore form state
  React.useEffect(() => {
    // restore once
    try {
      const raw = sessionStorage.getItem('verify:state');
      if (raw) {
        const s = JSON.parse(raw) as { bankCode?: string; bankName?: string; account?: string };
        if (s.bankCode) setBankCode(s.bankCode);
        if (s.bankName) setBankName(s.bankName);
        if (s.account) setAccount(s.account);
      }
    } catch {}
    setMounted(true);
  }, []);

  React.useEffect(() => {
    if (!mounted) return;
    const s = { bankCode, bankName, account };
    try { sessionStorage.setItem('verify:state', JSON.stringify(s)); } catch {}
  }, [mounted, bankCode, bankName, account]);

  function goNext() {
    if (!ne?.accountName || !bankCode || !account) return;
    // persist in MRU as well
    addRecent({ bankCode, bankName, accountNumber: account, accountName: ne.accountName });
    const sp = new URLSearchParams({ bankCode, bankName, account, accountName: ne.accountName });
    router.push(`/transfer/quote?${sp.toString()}`);
  }

  return (
    <RequireAuth>
      <div className="min-h-dvh p-6 max-w-2xl mx-auto space-y-6">
        <h1 className="text-2xl font-semibold">Verify recipient</h1>
        {unauthorized && (
          <div className="text-sm text-orange-700 border border-orange-200 rounded p-2">
            Your session has expired. Please <a className="underline" href="/auth/login">log in</a> and try again.
          </div>
        )}
        {error ? <div className="text-sm text-red-600 border border-red-200 rounded p-2">{error}</div> : null}
        <div className="grid gap-3 sm:grid-cols-2">
          <div className="sm:col-span-1">
            <label className="block text-sm mb-1">Bank</label>
            <div className="flex gap-2">
              <input className="flex-1 border rounded px-3 py-2" value={bankName} readOnly placeholder="Select bank" />
              <button className="border rounded px-3" onClick={() => setPickerOpen(true)}>Select</button>
            </div>
            {suggestions.length > 0 && (
              <div className="mt-2 flex flex-wrap gap-2">
                {suggestions.slice(0, 6).map((b) => (
                  <button key={b.bankCode} className="text-xs border rounded px-2 py-1" onClick={() => { setBankCode(b.bankCode); setBankName(b.name); }}>{b.name}</button>
                ))}
              </div>
            )}
          </div>
          <div className="sm:col-span-1">
            <label className="block text-sm mb-1">Account number</label>
            <input className="w-full border rounded px-3 py-2" value={account} onChange={(e) => setAccount(e.target.value)} placeholder="0123456789" />
            {suggestBusy && <div className="text-xs text-gray-500 mt-1">Checking bank suggestions…</div>}
          </div>
        </div>
        {!ne?.accountName ? (
          <div>
            <button className="bg-black text-white px-4 py-2 rounded" onClick={() => nameEnquiry.mutate()} disabled={nameEnquiry.isPending || !bankCode || !account}>
              {nameEnquiry.isPending ? "Verifying…" : "Verify account"}
            </button>
          </div>
        ) : (
          <div className="text-xs text-gray-600">
            Verified automatically. <button className="underline" onClick={() => nameEnquiry.mutate()} disabled={nameEnquiry.isPending}>Verify again</button>
          </div>
        )}
        {nameEnquiry.isPending && (
          <CardSkeleton lines={2} />
        )}
        {ne?.accountName && (
          <div className="border rounded p-3 text-sm flex items-center gap-2">
            <span className="inline-block h-2 w-2 rounded-full bg-blue-600" />
            <span className="font-medium">{ne.accountName}</span>
            <span className="text-gray-600">verified</span>
          </div>
        )}
        {/* Recent recipients (API + local MRU) */}
        {(recents.isLoading) ? (
          <div className="space-y-2">
            <CardSkeleton lines={2} />
          </div>
        ) : ((recents.data?.data?.length || loadRecents().length) ? (
          <div>
            <div className="text-sm font-medium mb-1">Recent recipients</div>
            <div className="flex flex-wrap gap-2">
              {(() => {
                const fromApi = (recents.data?.data || []).map(r => ({ bankCode: r.bankCode, bankName: r.bankName, accountNumber: r.accountNumber, accountName: r.accountName }));
                const mergedMap = new Map<string, { bankCode: string; bankName?: string; accountNumber: string; accountName?: string }>();
                const key = (x: any) => `${x.bankCode}:${x.accountNumber}`;
                for (const it of loadRecents()) mergedMap.set(key(it), it as any);
                for (const it of fromApi) mergedMap.set(key(it), it);
                return Array.from(mergedMap.values()).slice(0,8).map((r) => (
                <button
                  key={r.bankCode+":"+r.accountNumber}
                  className="text-xs border rounded px-2 py-1 hover:bg-gray-50"
                  onClick={() => {
                    setBankCode(r.bankCode); setBankName(r.bankName || ""); setAccount(r.accountNumber);
                    if (r.accountName) {
                      // Optimistic verify
                      setNe({ accountName: r.accountName, bankName: r.bankName, success: true });
                      // Silent re-verify in background
                      setTimeout(() => nameEnquiry.mutate(), 0);
                    }
                  }}
                >
                  {(r.bankName || r.bankCode)} • {r.accountNumber}
                </button>
                ));
              })()}
            </div>
          </div>
        ) : null)}
        <div>
          <button className="bg-black text-white px-4 py-2 rounded disabled:opacity-50" onClick={goNext} disabled={!ne?.accountName}>
            Confirm and continue
          </button>
        </div>
        <BankPicker open={pickerOpen} onClose={() => setPickerOpen(false)} onSelect={(b) => { setBankCode(b.bankCode); setBankName(b.name); }} />
      </div>
    </RequireAuth>
  );
}
