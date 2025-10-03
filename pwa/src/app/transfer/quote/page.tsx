"use client";
import React from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { useMutation } from "@tanstack/react-query";
import http from "@/lib/api";
import RequireAuth from "@/components/guards/require-auth";

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

export default function QuotePage() {
  const router = useRouter();
  const sp = useSearchParams();
  const bankCode = sp.get("bankCode") || "";
  const bankName = sp.get("bankName") || "";
  const account = sp.get("account") || "";
  const accountName = sp.get("accountName") || "";

  const [amount, setAmount] = React.useState<number>(Number(sp.get("amount") || 0));
  const [quoteRes, setQuoteRes] = React.useState<QuoteRes | null>(null);
  const [err, setErr] = React.useState<string | null>(null);
  const [ttlSec, setTtlSec] = React.useState(0);
  const [autoRefreshing, setAutoRefreshing] = React.useState(false);
  const [restored, setRestored] = React.useState(false);

  function formatLimitError(e: any): string {
    const d = e?.response?.data || {};
    const code = d.code || d.error || "";
    const msg = d.message || e.message;
    const min = d.minXaf ?? d.min ?? undefined;
    const max = d.maxXaf ?? d.max ?? undefined;
    const remainingDay = d.remainingXafDay ?? d.remainingDay ?? undefined;
    const remainingMonth = d.remainingXafMonth ?? d.remainingMonth ?? undefined;
    const parts: string[] = [];
    if (code) parts.push(`[${code}]`);
    if (msg) parts.push(String(msg));
    if (min !== undefined) parts.push(`Minimum: ${min} XAF`);
    if (max !== undefined) parts.push(`Maximum: ${max} XAF`);
    if (remainingDay !== undefined) parts.push(`Remaining today: ${remainingDay} XAF`);
    if (remainingMonth !== undefined) parts.push(`Remaining this month: ${remainingMonth} XAF`);
    return parts.join(" · ");
  }

  const quote = useMutation({
    mutationFn: async (vars: QuoteReq) => {
      setErr(null);
      const res = await http.post("/api/mobile/transfers/quote", vars);
      return res.data as QuoteRes;
    },
    onSuccess: (d) => setQuoteRes(d),
    onError: (e: any) => setErr(formatLimitError(e)),
  });

  // Restore saved quote state if same recipient
  React.useEffect(() => {
    if (restored) return;
    try {
      const raw = sessionStorage.getItem('quote:state');
      if (raw) {
        const s = JSON.parse(raw) as { amount?: number; bankCode?: string; account?: string };
        if (s && s.bankCode === bankCode && s.account === account && typeof s.amount === 'number' && s.amount > 0) {
          setAmount(s.amount);
        }
      }
    } catch {}
    setRestored(true);
  }, [restored, bankCode, account]);

  // Persist state
  React.useEffect(() => {
    try {
      sessionStorage.setItem('quote:state', JSON.stringify({ amount, bankCode, account }));
    } catch {}
  }, [amount, bankCode, account]);

  // Debounce quote while typing
  React.useEffect(() => {
    if (!amount || !bankCode || !account) { setQuoteRes(null); return; }
    const h = setTimeout(() => {
      quote.mutate({ amountXaf: Number(amount), bankCode, accountNumber: account });
    }, 400);
    return () => clearTimeout(h);
  }, [amount, bankCode, account]);

  // TTL countdown
  React.useEffect(() => {
    if (!quoteRes?.quote?.expiresAt) { setTtlSec(0); return; }
    const expires = new Date(quoteRes.quote.expiresAt).getTime();
    const tick = () => setTtlSec(Math.max(0, Math.floor((expires - Date.now()) / 1000)));
    tick();
    const id = setInterval(tick, 1000);
    return () => clearInterval(id);
  }, [quoteRes?.quote?.expiresAt]);

  // Auto re-quote when expired to restart countdown with latest rate
  React.useEffect(() => {
    if (!quoteRes || !amount || !bankCode || !account) return;
    if (ttlSec > 0) { setAutoRefreshing(false); return; }
    if (quote.isPending || autoRefreshing) return;
    setAutoRefreshing(true);
    quote.mutate({ amountXaf: Number(amount), bankCode, accountNumber: account }, {
      onSettled: () => setAutoRefreshing(false),
    });
  }, [ttlSec, quoteRes, amount, bankCode, account]);

  function proceed() {
    if (!quoteRes?.quote) return;
    // Persist selected quote + recipient for confirm page (short URL)
    try {
      const payload = {
        quote: quoteRes.quote,
        recipient: { bankCode, bankName, account, accountName },
      };
      sessionStorage.setItem('quote:selected', JSON.stringify(payload));
      sessionStorage.removeItem('quote:state');
    } catch {}
    const s = new URLSearchParams({
      quoteId: String(quoteRes.quote.id),
    });
    router.push(`/transfer/confirm?${s.toString()}`);
  }

  const receiveNgn = quoteRes?.quote?.receiveNgnMinor ? (quoteRes.quote.receiveNgnMinor / 100).toFixed(2) : "0.00";
  
  return (
    <RequireAuth>
      <div className="min-h-dvh p-6 max-w-2xl mx-auto space-y-6">
        <h1 className="text-2xl font-semibold">Enter amount</h1>

        {/* Recipient summary */}
        <div className="border rounded p-3 text-sm flex items-center justify-between">
          <div>
            <div className="font-medium">{accountName || "Recipient"}</div>
            <div className="text-gray-600">{bankName} • {account}</div>
          </div>
        </div>

        {err ? <div className="text-sm text-red-600 border border-red-200 rounded p-2">{err}</div> : null}

        <div className="space-y-3">
          <label className="block text-sm">Amount (XAF)</label>
          <input
            className="w-full border rounded px-3 py-2"
            type="number"
            min={1}
            value={amount || ""}
            onChange={(e) => setAmount(parseInt(e.target.value || "0", 10))}
            placeholder="10000"
          />
        </div>

        {quoteRes?.quote && (
          <div className="border rounded p-3 text-sm space-y-1">
            <div>Rate: <span className="font-medium">1 XAF to NGN {quoteRes.quote.adjustedRate}</span></div>
            <div>Fees: <span className="font-medium">{quoteRes.quote.feeTotalXaf} XAF</span></div>
            <div className="text-gray-800">Total to pay: <span className="font-medium">{quoteRes.quote.totalPayXaf} XAF</span></div>
            <div>Receiver gets:</div>
            <div className="text-2xl font-bold">₦ {receiveNgn}</div>
            <div className="text-xs text-gray-600">Quote expires {new Date(quoteRes.quote.expiresAt).toLocaleTimeString()} ({ttlSec}s left){autoRefreshing ? ' – refreshing…' : ''}</div>
            <div className="pt-2">
              <button
                className={`px-4 py-2 rounded text-white ${ttlSec > 0 ? 'bg-black' : 'bg-gray-400'}`}
                disabled={ttlSec <= 0}
                onClick={proceed}
              >
                Confirm quote
              </button>
            </div>
          </div>
        )}
      </div>
    </RequireAuth>
  );
}
