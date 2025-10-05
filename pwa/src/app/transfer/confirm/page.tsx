"use client";
import React from "react";
import { CardSkeleton } from "@/components/ui/skeleton";
import PageHeader from "@/components/ui/page-header";
import { useMutation } from "@tanstack/react-query";
import { useRouter, useSearchParams } from "next/navigation";
import http from "@/lib/api";
import RequireAuth from "@/components/guards/require-auth";
import { validateCameroon, providerMeta, formatForDisplay } from "@/lib/phone";

type ConfirmReq = { quoteId: number; bankCode: string; accountNumber: string; msisdn: string; accountName?: string; nameEnquiryRef?: string };

type ConfirmRes = {
  success: boolean;
  transfer: { id: number; status: string; payinRef?: string };
};

type StatusRes = { success?: boolean; status?: string; message?: string };

type Quote = {
  id: number;
  ref: string;
  amountXaf: number;
  feeTotalXaf: number;
  totalPayXaf: number;
  receiveNgnMinor: number;
  adjustedRate: number;
  expiresAt: string;
};

export default function ConfirmPage() {
  return (
    <React.Suspense fallback={<div className="min-h-dvh grid place-items-center p-6 text-sm text-gray-600">Loading…</div>}>
      <ConfirmInner />
    </React.Suspense>
  );
}

function ConfirmInner() {
  const router = useRouter();
  const sp = useSearchParams();
  const quoteId = Number(sp.get("quoteId") || 0);
  const [bankCode, setBankCode] = React.useState("");
  const [bankName, setBankName] = React.useState("");
  const [accountNumber, setAccountNumber] = React.useState("");
  const [accountName, setAccountName] = React.useState("");
  const [neRef, setNeRef] = React.useState<string | null>(null);
  const [quote, setQuote] = React.useState<Quote | null>(null);
  const [ttlSec, setTtlSec] = React.useState(0);

  const [msisdn, setMsisdn] = React.useState("");
  const [transferId, setTransferId] = React.useState<number | null>(null);
  const [topError, setTopError] = React.useState<string | null>(null);
  const [payinStatus, setPayinStatus] = React.useState<string | null>(null);
  const [payoutStatus, setPayoutStatus] = React.useState<string | null>(null);
  const [autoPayoutStarted, setAutoPayoutStarted] = React.useState(false);
  const [cooldownUntil, setCooldownUntil] = React.useState(0);
  const lastConfirmVarsRef = React.useRef<ConfirmReq | null>(null);
  const retried429Ref = React.useRef(false);

  // Number formatters and derived display values
  const nf = React.useMemo(() => new Intl.NumberFormat('en-NG'), []);
  const ngnFmt = React.useMemo(() => new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN', minimumFractionDigits: 2 }), []);

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

  const confirm = useMutation({
    mutationFn: async (vars: ConfirmReq) => {
      setTopError(null);
      const res = await http.post("/api/mobile/transfers/confirm", vars);
      return res.data as ConfirmRes;
    },
    onSuccess: (data) => {
      setTransferId(data.transfer.id);
      setPayinStatus(data.transfer.status || "payin_pending");
      // Redirect to dedicated processing screen
      router.replace(`/transfer/processing?transferId=${data.transfer.id}`);
    },
    onError: (e: any) => {
      const status = e?.response?.status;
      if (status === 429 && !retried429Ref.current) {
        // Cooldown and retry once
        const until = Date.now() + 12000;
        setCooldownUntil(until);
        setTopError("Please wait a few seconds before trying again.");
        retried429Ref.current = true;
        setTimeout(() => {
          if (Date.now() >= until && lastConfirmVarsRef.current) {
            confirm.mutate(lastConfirmVarsRef.current);
          }
        }, 12050);
        return;
      }
      setTopError(formatLimitError(e));
    },
  });

  // Payout initiation and polling
  const payoutPoll = useMutation({
    mutationFn: async () => {
      if (!transferId) return { status: "" } as StatusRes;
      const res = await http.post(`/api/mobile/transfers/${transferId}/payout/status`);
      return res.data as StatusRes;
    },
    onSuccess: (d) => setPayoutStatus(d.status || null),
    onError: (e: any) => setTopError(e?.response?.data?.message || e.message),
  });

  // Manual re-quote to refresh rate/expiry using stored recipient
  const requote = useMutation({
    mutationFn: async () => {
      if (!quote) return null as any;
      const res = await http.post('/api/mobile/transfers/quote', {
        amountXaf: quote.amountXaf,
        bankCode: bankCode,
        accountNumber: accountNumber,
      });
      return res.data as { quote: Quote };
    },
    onSuccess: (d) => {
      if (d?.quote) {
        setQuote(d.quote);
        // update URL with new quoteId only
        const qp = new URLSearchParams({ quoteId: String(d.quote.id) });
        router.replace(`/transfer/confirm?${qp.toString()}`);
        try {
          const payload = { quote: d.quote, recipient: { bankCode, bankName, account: accountNumber, accountName } };
          sessionStorage.setItem('quote:selected', JSON.stringify(payload));
        } catch {}
      }
    },
    onError: (e: any) => setTopError(e?.response?.data?.message || e.message),
  });

  const poll = useMutation({
    mutationFn: async () => {
      if (!transferId) return { status: "" } as StatusRes;
      const res = await http.post(`/api/mobile/transfers/${transferId}/payin/status`);
      return res.data as StatusRes;
    },
    onSuccess: (d) => setPayinStatus(d.status || null),
    onError: (e: any) => setTopError(formatLimitError(e)),
  });

  // Auto-poll every 3s when pending, stop on success/failure
  React.useEffect(() => {
    if (!transferId) return;
    if (!payinStatus || payinStatus.includes("pending")) {
      const id = setInterval(() => poll.mutate(), 3000);
      return () => clearInterval(id);
    }
    // When pay-in succeeds, enable payout step (no immediate redirect).
  }, [transferId, payinStatus]);

  // Redirect to success after payout success
  React.useEffect(() => {
    if (!transferId) return;
    if (payoutStatus === 'success' || payoutStatus === 'completed') {
      const qp = new URLSearchParams({
        bankName,
        accountName,
        account: accountNumber,
        amount: String(quote?.amountXaf || 0),
        receiveMinor: String(quote?.receiveNgnMinor || 0),
      });
      router.replace(`/transfer/${transferId}/success?${qp.toString()}`);
    }
  }, [transferId, payoutStatus]);

  // Auto-sync payout status (backend initiates); poll until terminal
  React.useEffect(() => {
    if (!transferId) return;
    const ok = (payinStatus === 'success' || payinStatus === 'completed');
    if (!ok) return;
    const terminal = (payoutStatus === 'success' || payoutStatus === 'completed' || payoutStatus === 'failed' || payoutStatus === 'error');
    if (terminal) return;
    const id = setInterval(() => payoutPoll.mutate(), 3000);
    return () => clearInterval(id);
  }, [transferId, payinStatus, payoutStatus]);

  // Load selected quote + recipient from sessionStorage (short URL)
  React.useEffect(() => {
    try {
      const raw = sessionStorage.getItem('quote:selected');
      if (raw) {
        const s = JSON.parse(raw) as { quote?: Quote; recipient?: { bankCode: string; bankName: string; account: string; accountName: string } };
        if (s?.recipient) {
          setBankCode(s.recipient.bankCode || "");
          setBankName(s.recipient.bankName || "");
          setAccountNumber(s.recipient.account || "");
          setAccountName(s.recipient.accountName || "");
          try {
            const k = `ne:ref:${s.recipient.bankCode}:${s.recipient.account}`;
            const ref = sessionStorage.getItem(k);
            setNeRef(ref || null);
          } catch {}
        }
        if (s?.quote) setQuote(s.quote);
        if (!s?.recipient || !s?.quote) {
          // Missing data: go back to verify
          router.replace('/transfer/verify');
        }
      } else {
        router.replace('/transfer/verify');
      }
    } catch {}
  }, []);

  // TTL countdown on confirm page
  React.useEffect(() => {
    if (!quote?.expiresAt) { setTtlSec(0); return; }
    const expires = new Date(quote.expiresAt).getTime();
    const tick = () => setTtlSec(Math.max(0, Math.floor((expires - Date.now()) / 1000)));
    tick();
    const id = setInterval(tick, 1000);
    return () => clearInterval(id);
  }, [quote?.expiresAt]);

  // No auto refresh; user triggers re-quote manually.

  const receiveNgn = quote ? (quote.receiveNgnMinor / 100) : 0;
  const impliedRate = quote ? (receiveNgn / Math.max(1, quote.amountXaf)) : null; // NGN per XAF

  return (
    <RequireAuth>
      <div className="min-h-dvh p-6 max-w-xl mx-auto space-y-6">
        {/* Header */}
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2">
            <button onClick={() => router.back()} aria-label="Back" className="text-xl">‹</button>
            <h1 className="text-lg font-semibold">Transfer To Bank Account</h1>
          </div>
          <button className="text-sm underline" onClick={() => router.push('/transfers')}>History</button>
        </div>
        {topError ? (
          <div className="text-sm text-red-600 border border-red-200 rounded p-2">{topError}</div>
        ) : null}

        {/* Recipient summary */}
        <section className="bg-gray-50 border rounded-xl p-3 flex items-center gap-3">
          <div className="h-9 w-9 rounded-full bg-gray-100 flex items-center justify-center text-xs">{(bankName || '•').slice(0,1).toUpperCase()}</div>
          <div className="flex-1 min-w-0">
            <div className="text-sm font-medium truncate">{accountName || "Recipient"}</div>
            <div className="text-xs text-blue-600 truncate">{accountNumber}</div>
            <div className="text-xs text-gray-600 truncate">{bankName}</div>
            {neRef ? (
              <div className="text-[11px] text-gray-500 truncate mt-0.5">Verified ref: {neRef}</div>
            ) : null}
          </div>
        </section>

        {/* Quote details */}
        <section className="border rounded-xl p-4 space-y-2 text-sm">
          <div className="grid grid-cols-2 gap-2">
            <div>Amount (XAF): <span className="font-medium">{quote ? nf.format(quote.amountXaf) : '—'}</span></div>
            <div>Receiver (NGN): <span className="text-lg font-semibold">{ngnFmt.format(receiveNgn)}</span></div>
            <div>Fees: <span className="font-medium">{quote ? nf.format(quote.feeTotalXaf) : 0} XAF</span></div>
            <div>Total pay: <span className="font-medium">{quote ? nf.format(quote.totalPayXaf) : '—'} XAF</span></div>
          </div>
          {/* Rate line */}
          {quote ? (
            <div className="text-xs text-gray-700 flex items-center gap-2">
              <span className="inline-block h-4 w-4 rounded bg-gray-900" />
              <span>Rate: 1 XAF = NGN {impliedRate?.toFixed(2)}</span>
            </div>
          ) : null}
          <div className="text-xs text-gray-600 flex items-center gap-2">
            <span>Quote expires {quote ? new Date(quote.expiresAt).toLocaleTimeString() : '—'} ({ttlSec}s left)</span>
            {ttlSec <= 0 && (
              <button type="button" className="underline" disabled={requote.isPending} onClick={() => requote.mutate()}>
                {requote.isPending ? 'Refreshing…' : 'Refresh rate'}
              </button>
            )}
          </div>
        </section>

        {/* MSISDN */}
        <form
          className="space-y-3"
          onSubmit={(e) => {
            e.preventDefault();
            const v = validateCameroon(msisdn);
            if (!v.valid) {
              setTopError(v.error || "Invalid phone number");
              return;
            }
            const vars: ConfirmReq = { quoteId: Number(quoteId), bankCode, accountNumber, msisdn: v.normalized };
            if (accountName) vars.accountName = accountName;
            if (neRef) vars.nameEnquiryRef = neRef;
            lastConfirmVarsRef.current = vars;
            if (Date.now() < cooldownUntil) {
              setTopError('Please wait a few seconds before trying again.');
              return;
            }
            retried429Ref.current = false; // reset per attempt
            confirm.mutate(vars);
          }}
        >
          <div>
            <label className="block text-sm mb-1">Payer phone (MSISDN)</label>
            <input
              className="w-full border rounded px-3 py-2"
              value={msisdn}
              onChange={(e) => setMsisdn(e.target.value)}
              placeholder="e.g. 2376XXXXXXXX"
              required
            />
            {(() => {
              const v = validateCameroon(msisdn);
              const meta = providerMeta(v.provider);
              return (
                <div className="mt-1 flex items-center gap-2 text-xs">
                  <span className="text-gray-600">{formatForDisplay(msisdn)}</span>
                  {meta ? (
                    <span className={`px-2 py-0.5 rounded ${meta.color}`}>{meta.label}</span>
                  ) : null}
                </div>
              );
            })()}
          </div>
          <button
            type="submit"
            className="w-full h-12 rounded-full text-white disabled:opacity-50 disabled:cursor-not-allowed"
            disabled={confirm.isPending || !!transferId}
            style={{
              backgroundColor:
                payinStatus?.includes('pending') ? '#6b7280' :
                (payinStatus === 'success' || payinStatus === 'completed') ? '#10b981' :
                (payinStatus === 'failed') ? '#ef4444' : '#059669' // emerald-600
            }}
          >
            <span className="inline-flex items-center gap-2">
              {(() => {
                const showSpin = confirm.isPending || (!!transferId && (payinStatus?.includes('pending') || !payinStatus));
                return showSpin ? (
                  <svg className="animate-spin h-4 w-4 text-white" viewBox="0 0 24 24">
                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                  </svg>
                ) : null;
              })()}
              {(() => {
                if (confirm.isPending) return "Processing…";
                if (!transferId) return "Pay with Mobile Money";
                if (payinStatus?.includes('pending') || !payinStatus) return "Pending…";
                if (payinStatus === 'success' || payinStatus === 'completed') return "Completed";
                if (payinStatus === 'failed') return "Failed";
                return "Pending…";
              })()}
            </span>
          </button>
        </form>

        {/* Processing now handled in /transfer/processing */}
        {confirm.isPending && (
          <div className="mt-3">
            <CardSkeleton lines={2} />
          </div>
        )}
      </div>
    </RequireAuth>
  );
}
