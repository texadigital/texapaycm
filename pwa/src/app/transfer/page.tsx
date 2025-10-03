"use client";
import React from "react";
import { useRouter } from "next/navigation";
import { useMutation } from "@tanstack/react-query";
import http from "@/lib/api";
import RequireAuth from "@/components/guards/require-auth";
import BankPicker, { Bank } from "@/components/banks/bank-picker";

type NameEnquiryReq = { bankCode: string; accountNumber: string };

type NameEnquiryRes = {
  success?: boolean;
  accountName?: string;
  bankName?: string;
  reference?: string;
  message?: string;
};

type QuoteReq = { amountXaf: number; bankCode: string; accountNumber: string };

type QuoteRes = {
  success: boolean;
  quote: {
    id: number;
    ref: string;
    amountXaf: number;
    feeTotalXaf: number;
    totalPayXaf: number;
    receiveNgnMinor: number;
    adjustedRate: number;
    expiresAt: string;
  };
};

export default function TransferPage() {
  const router = useRouter();
  React.useEffect(() => {
    // Seamless move to new multi-step flow
    router.replace('/transfer/verify');
  }, [router]);
  // Name Enquiry form state
  const [neBankCode, setNeBankCode] = React.useState("");
  const [neBankName, setNeBankName] = React.useState("");
  const [neAccount, setNeAccount] = React.useState("");
  const [neResult, setNeResult] = React.useState<NameEnquiryRes | null>(null);
  const [neError, setNeError] = React.useState<string | null>(null);
  const [pickerOpen, setPickerOpen] = React.useState(false);
  const [suggestions, setSuggestions] = React.useState<Bank[]>([]);
  const [suggestBusy, setSuggestBusy] = React.useState(false);

  const nameEnquiry = useMutation({
    mutationFn: async (vars: NameEnquiryReq) => {
      setNeError(null);
      setNeResult(null);
      const res = await http.post("/api/mobile/transfers/name-enquiry", vars);
      return res.data as NameEnquiryRes;
    },
    onSuccess: (data) => setNeResult(data),
    onError: (e: any) => setNeError(e?.response?.data?.message || e.message),
  });

  // Quote form state (reuses bank+account by default)
  const [amount, setAmount] = React.useState<number>(0);
  const [qBankCode, setQBankCode] = React.useState("");
  const [qBankName, setQBankName] = React.useState("");
  const [qAccount, setQAccount] = React.useState("");
  const [quoteRes, setQuoteRes] = React.useState<QuoteRes | null>(null);
  const [quoteError, setQuoteError] = React.useState<string | null>(null);
  const [ttlSec, setTtlSec] = React.useState<number>(0);

  const quote = useMutation({
    mutationFn: async (vars: QuoteReq) => {
      setQuoteError(null);
      setQuoteRes(null);
      const res = await http.post("/api/mobile/transfers/quote", vars);
      return res.data as QuoteRes;
    },
    onSuccess: (data) => setQuoteRes(data),
    onError: (e: any) => setQuoteError(e?.response?.data?.message || e.message),
  });

  React.useEffect(() => {
    // convenience: copy bank/account from name enquiry into quote panel
    if (neBankCode && !qBankCode) setQBankCode(neBankCode);
    if (neBankName && !qBankName) setQBankName(neBankName);
    if (neAccount && !qAccount) setQAccount(neAccount);
  }, [neBankCode, neBankName, neAccount, qBankCode, qBankName, qAccount]);

  // Auto-suggest banks from account number
  React.useEffect(() => {
    const acct = neAccount.trim();
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
          setNeBankCode(bank.bankCode);
          setNeBankName(bank.name || "");
          setQBankCode(bank.bankCode);
          setQBankName(bank.name || "");
        }
      } catch (e) {
        if (!cancelled) setSuggestions([]);
      } finally {
        if (!cancelled) setSuggestBusy(false);
      }
    }
    runSuggest();
    return () => { cancelled = true; };
  }, [neAccount]);

  // Drive TTL countdown when a quote exists
  React.useEffect(() => {
    if (!quoteRes?.quote?.expiresAt) {
      setTtlSec(0);
      return;
    }
    const expires = new Date(quoteRes.quote.expiresAt).getTime();
    const tick = () => {
      const now = Date.now();
      const left = Math.max(0, Math.floor((expires - now) / 1000));
      setTtlSec(left);
    };
    tick();
    const id = setInterval(tick, 1000);
    return () => clearInterval(id);
  }, [quoteRes?.quote?.expiresAt]);

  return (
    <RequireAuth>
    <div className="min-h-dvh p-6 max-w-2xl mx-auto space-y-8">
      <h1 className="text-2xl font-semibold">Transfers</h1>

      {/* Name Enquiry */}
      <section className="space-y-3">
        <h2 className="text-lg font-semibold">Name enquiry</h2>
        {neError ? (
          <div className="text-sm text-red-600 border border-red-200 rounded p-2">{neError}</div>
        ) : null}
        <form
          className="grid gap-3 sm:grid-cols-2"
          onSubmit={(e) => {
            e.preventDefault();
            nameEnquiry.mutate({ bankCode: neBankCode, accountNumber: neAccount });
          }}
        >
          <div className="sm:col-span-1">
            <label className="block text-sm mb-1">Bank</label>
            <div className="flex gap-2">
              <input
                className="flex-1 border rounded px-3 py-2"
                value={neBankName}
                onChange={(e) => setNeBankName(e.target.value)}
                placeholder="Select bank"
                readOnly
              />
              <button type="button" className="border rounded px-3" onClick={() => setPickerOpen(true)}>Select</button>
            </div>
            {suggestions.length > 0 && (
              <div className="mt-2 flex flex-wrap gap-2">
                {suggestions.slice(0,6).map((b) => (
                  <button
                    type="button"
                    key={`s-${b.bankCode}`}
                    className="text-xs border rounded px-2 py-1 hover:bg-gray-50"
                    onClick={() => { setNeBankCode(b.bankCode); setNeBankName(b.name); setQBankCode(b.bankCode); setQBankName(b.name); }}
                  >{b.name}</button>
                ))}
              </div>
            )}
          </div>
          <div className="sm:col-span-1">
            <label className="block text-sm mb-1">Account number</label>
            <input
              className="w-full border rounded px-3 py-2"
              value={neAccount}
              onChange={(e) => setNeAccount(e.target.value)}
              placeholder="0123456789"
              required
            />
            {suggestBusy && <div className="text-xs text-gray-500 mt-1">Checking bank suggestions…</div>}
          </div>
          <div className="sm:col-span-2">
            <button
              type="submit"
              className="w-full bg-black text-white px-4 py-2 rounded disabled:opacity-50"
              disabled={nameEnquiry.isPending}
            >
              {nameEnquiry.isPending ? "Checking..." : "Check name"}
            </button>
          </div>
        </form>
        {neResult && (
          <div className="border rounded p-3 text-sm">
            <div>Account name: <span className="font-medium">{neResult.accountName || "—"}</span></div>
            <div>Bank: <span className="font-medium">{neResult.bankName || "—"}</span></div>
            {neResult.reference ? (
              <div>Reference: <span className="font-mono">{neResult.reference}</span></div>
            ) : null}
          </div>
        )}
      </section>

      {/* Quote */}
      <section className="space-y-3">
        <h2 className="text-lg font-semibold">Create quote</h2>
        {quoteError ? (
          <div className="text-sm text-red-600 border border-red-200 rounded p-2">{quoteError}</div>
        ) : null}
        <form
          className="grid gap-3 sm:grid-cols-2"
          onSubmit={(e) => {
            e.preventDefault();
            quote.mutate({
              amountXaf: Number(amount),
              bankCode: qBankCode,
              accountNumber: qAccount,
            });
          }}
        >
          <div>
            <label className="block text-sm mb-1">Amount (XAF)</label>
            <input
              className="w-full border rounded px-3 py-2"
              type="number"
              min={1}
              value={amount || ""}
              onChange={(e) => setAmount(parseInt(e.target.value || "0", 10))}
              placeholder="10000"
              required
            />
          </div>
          <div>
            <label className="block text-sm mb-1">Bank</label>
            <div className="flex gap-2">
              <input
                className="flex-1 border rounded px-3 py-2"
                value={qBankName}
                onChange={(e) => setQBankName(e.target.value)}
                placeholder="Select bank"
                readOnly
              />
              <button type="button" className="border rounded px-3" onClick={() => setPickerOpen(true)}>Select</button>
            </div>
          </div>
          <div className="sm:col-span-2">
            <label className="block text-sm mb-1">Account number</label>
            <input
              className="w-full border rounded px-3 py-2"
              value={qAccount}
              onChange={(e) => setQAccount(e.target.value)}
              placeholder="0123456789"
              required
            />
          </div>
          <div className="sm:col-span-2">
            <button
              type="submit"
              className="w-full bg-black text-white px-4 py-2 rounded disabled:opacity-50"
              disabled={quote.isPending}
            >
              {quote.isPending ? "Quoting..." : "Create quote"}
            </button>
          </div>
        </form>

        {quoteRes && (
          <div className="border rounded p-3 text-sm space-y-1">
            <div>Quote ref: <span className="font-mono">{quoteRes.quote.ref}</span></div>
            <div>Amount: {quoteRes.quote.amountXaf} XAF</div>
            <div>Fees: {quoteRes.quote.feeTotalXaf} XAF</div>
            <div>Total pay: {quoteRes.quote.totalPayXaf} XAF</div>
            <div>Receive (NGN minor): {quoteRes.quote.receiveNgnMinor}</div>
            <div>Rate: {quoteRes.quote.adjustedRate}</div>
            <div className="flex items-center gap-2">
              <span>Expires: {new Date(quoteRes.quote.expiresAt).toLocaleString()}</span>
              {ttlSec > 0 ? (
                <span className="text-xs text-gray-600">({ttlSec}s left)</span>
              ) : (
                <span className="text-xs text-red-600">Expired</span>
              )}
            </div>
            <div className="pt-2 flex items-center gap-2">
              <a
                href={`/transfer/confirm?quote=${encodeURIComponent(String(quoteRes.quote.id))}`}
                className={`px-3 py-2 rounded text-white ${ttlSec > 0 ? 'bg-black' : 'bg-gray-400 pointer-events-none'}`}
              >
                Continue to confirm
              </a>
              <button
                className="border rounded px-3 py-2"
                onClick={() => setQuoteRes(null)}
              >
                Re-quote
              </button>
            </div>
          </div>
        )}
      </section>
      <BankPicker
        open={pickerOpen}
        onClose={() => setPickerOpen(false)}
        onSelect={(b: Bank) => {
          setNeBankCode(b.bankCode);
          setNeBankName(b.name);
          setQBankCode(b.bankCode);
          setQBankName(b.name);
        }}
      />
    </div>
    </RequireAuth>
  );
}
