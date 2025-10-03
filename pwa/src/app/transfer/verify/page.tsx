"use client";
import React from "react";
import { useMutation } from "@tanstack/react-query";
import http from "@/lib/api";
import RequireAuth from "@/components/guards/require-auth";
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

  const nameEnquiry = useMutation({
    mutationFn: async () => {
      setError(null);
      setNe(null);
      const res = await http.post("/api/mobile/transfers/name-enquiry", { bankCode, accountNumber: account });
      return res.data as NameEnquiryRes;
    },
    onSuccess: (d) => setNe(d),
    onError: (e: any) => setError(e?.response?.data?.message || e.message),
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
      } catch {
        if (!cancelled) setSuggestions([]);
      } finally {
        if (!cancelled) setSuggestBusy(false);
      }
    }
    runSuggest();
    return () => { cancelled = true; };
  }, [account]);

  function goNext() {
    if (!ne?.accountName || !bankCode || !account) return;
    const sp = new URLSearchParams({ bankCode, bankName, account, accountName: ne.accountName });
    router.push(`/transfer/quote?${sp.toString()}`);
  }

  return (
    <RequireAuth>
      <div className="min-h-dvh p-6 max-w-2xl mx-auto space-y-6">
        <h1 className="text-2xl font-semibold">Verify recipient</h1>
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
        <div>
          <button className="bg-black text-white px-4 py-2 rounded" onClick={() => nameEnquiry.mutate()} disabled={nameEnquiry.isPending || !bankCode || !account}>
            {nameEnquiry.isPending ? "Verifying…" : "Verify account"}
          </button>
        </div>
        {ne?.accountName && (
          <div className="border rounded p-3 text-sm flex items-center gap-2">
            <span className="inline-block h-2 w-2 rounded-full bg-blue-600" />
            <span className="font-medium">{ne.accountName}</span>
            <span className="text-gray-600">verified</span>
          </div>
        )}
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
