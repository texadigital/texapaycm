"use client";
import React from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { useMutation } from "@tanstack/react-query";
import http from "@/lib/api";
import RequireAuth from "@/components/guards/require-auth";
import { getScopedItem, setScopedItem } from "@/lib/storage";

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
    ttlSeconds?: number;
    rateDisplay?: string | null;
    components?: {
      fxMarginBps?: number | null;
      percentBps?: number | null;
      fixedFeeXaf?: number | null;
      percentFeeXaf?: number | null;
      levyXaf?: number | null;
      totalFeeXaf?: number | null;
    } | null;
  };
};

export default function QuotePage() {
  return (
    <React.Suspense fallback={<div className="min-h-dvh grid place-items-center p-6 text-sm text-gray-600">Loading…</div>}>
      <QuoteInner />
    </React.Suspense>
  );
}

function QuoteInner() {
  const router = useRouter();
  const sp = useSearchParams();
  const bankCode = sp.get("bankCode") || "";
  const bankName = sp.get("bankName") || "";
  const account = sp.get("account") || "";
  const accountName = sp.get("accountName") || "";

  const [amount, setAmount] = React.useState<number>(Number(sp.get("amount") || 0));
  const [amountText, setAmountText] = React.useState<string>("");
  const [quoteRes, setQuoteRes] = React.useState<QuoteRes | null>(null);
  const [err, setErr] = React.useState<string | null>(null);
  const [ttlSec, setTtlSec] = React.useState(0);
  const [autoRefreshing, setAutoRefreshing] = React.useState(false);
  const [restored, setRestored] = React.useState(false);
  const [previewRate, setPreviewRate] = React.useState<number | null>(null); // NGN per XAF
  const controllerRef = React.useRef<AbortController | null>(null);
  const lastKeyRef = React.useRef<string>("");
  const [rateLimitedUntil, setRateLimitedUntil] = React.useState<number>(0);
  const debounceRef = React.useRef<NodeJS.Timeout | null>(null);

  // Number formatters
  const nf = React.useMemo(() => new Intl.NumberFormat('en-NG'), []);
  const ngnFmt = React.useMemo(() => new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN', minimumFractionDigits: 2 }), []);

  // Initialize amountText from amount
  React.useEffect(() => {
    setAmountText(amount > 0 ? nf.format(amount) : "");
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

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
      // Cancel any in-flight request
      try { controllerRef.current?.abort(); } catch {}
      controllerRef.current = new AbortController();
      const res = await http.post("/api/mobile/transfers/quote", vars, { signal: controllerRef.current.signal });
      return res.data as QuoteRes;
    },
    onSuccess: (d) => {
      setQuoteRes(d);
      try {
        const key = `quote:lock:${bankCode}:${account}:${amount}`;
        setScopedItem(key, d);
        // Save last implied rate as a fallback for instant preview
        const implied = (d.quote.receiveNgnMinor / 100) / Math.max(1, d.quote.amountXaf);
        try { sessionStorage.setItem('rate:last', JSON.stringify({ rate: implied, at: Date.now() })); } catch {}
        if (!previewRate) setPreviewRate(implied);
      } catch {}
    },
    onError: (e: any) => {
      if (e?.response?.status === 429) {
        // Respect Retry-After header if present
        const raHdr = e?.response?.headers?.['retry-after'];
        const ra = Math.max(5, Math.min(120, Number(raHdr) || Number(e?.response?.data?.retryAfterSeconds) || 12));
        const until = Date.now() + ra * 1000;
        setRateLimitedUntil(until);
        setErr(`Please wait ${ra}s before trying again.`);
      } else {
        // Persist structured limits if present for the Limits page fallback
        try {
          const d = e?.response?.data || {};
          const snapshot = {
            minXaf: d.minXaf ?? d.min ?? undefined,
            maxXaf: d.maxXaf ?? d.max ?? undefined,
            usedToday: d.usedToday ?? undefined,
            usedMonth: d.usedMonth ?? undefined,
            remainingXafDay: d.remainingXafDay ?? d.remainingDay ?? undefined,
            remainingXafMonth: d.remainingXafMonth ?? d.remainingMonth ?? undefined,
            dailyCap: d.dailyCap ?? undefined,
            monthlyCap: d.monthlyCap ?? undefined,
            updatedAt: new Date().toISOString(),
          };
          sessionStorage.setItem('limits:last', JSON.stringify(snapshot));
        } catch {}
        setErr(formatLimitError(e));
      }
    },
  });

  // Debounced auto-quote on valid input changes (fintech-style: live pricing)
  React.useEffect(() => {
    if (!amount || !bankCode || !account) return;
    if (Date.now() < rateLimitedUntil) return;
    const key = `${bankCode}:${account}:${amount}`;
    if (lastKeyRef.current === key && quoteRes?.quote) return; // no change
    if (debounceRef.current) clearTimeout(debounceRef.current);
    debounceRef.current = setTimeout(() => {
      lastKeyRef.current = key;
      quote.mutate({ amountXaf: Number(amount), bankCode, accountNumber: account });
    }, 450);
    return () => { if (debounceRef.current) clearTimeout(debounceRef.current); };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [amount, bankCode, account, rateLimitedUntil]);

  // Fetch lightweight FX preview for instant calculation (NGN per XAF)
  React.useEffect(() => {
    let cancelled = false;
    // 1) try cached preview
    try {
      const raw = sessionStorage.getItem('rate:preview');
      if (raw) {
        const cached = JSON.parse(raw) as { rate?: number; at?: number };
        if (cached?.rate && cached?.at && Date.now() - cached.at < 10 * 60_000) {
          setPreviewRate(cached.rate);
        }
      }
    } catch {}
    // 2) network fetch in background
    (async function fetchPreview() {
      try {
        const res = await http.get('/api/mobile/pricing/rate-preview');
        const data = res.data || {};
        const usdToXaf = Number(data.usd_to_xaf || data.usdToXaf || 0);
        const usdToNgn = Number(data.usd_to_ngn || data.usdToNgn || 0);
        if (!cancelled && usdToXaf > 0 && usdToNgn > 0) {
          const cross = usdToNgn / usdToXaf; // NGN per XAF
          setPreviewRate(cross);
          try { sessionStorage.setItem('rate:preview', JSON.stringify({ rate: cross, at: Date.now() })); } catch {}
        }
      } catch {}
    })();
    return () => { cancelled = true; };
  }, []);

  // Restore saved quote state if same recipient (scoped per user)
  React.useEffect(() => {
    if (restored) return;
    try {
      const s = getScopedItem<{ amount?: number; bankCode?: string; account?: string }>('quote:state');
        if (s && s.bankCode === bankCode && s.account === account && typeof s.amount === 'number' && s.amount > 0) {
          setAmount(s.amount);
          setAmountText(nf.format(s.amount));
        }
    } catch {}
    setRestored(true);
  }, [restored, bankCode, account]);

  // Persist state (scoped per user)
  React.useEffect(() => {
    try {
      setScopedItem('quote:state', { amount, bankCode, account });
    } catch {}
  }, [amount, bankCode, account]);

  // Preview-only: reuse quote lock if valid, otherwise clear quote and show preview
  React.useEffect(() => {
    if (!amount || !bankCode || !account) { setQuoteRes(null); return; }
    if (Date.now() < rateLimitedUntil) return; // respect cooldown
    try {
      const lockKey = `quote:lock:${bankCode}:${account}:${amount}`;
      const lock = getScopedItem<QuoteRes>(lockKey);
      const exp = lock?.quote?.expiresAt ? new Date(lock.quote.expiresAt).getTime() : 0;
      if (lock?.quote && exp > Date.now()) {
        setQuoteRes(lock);
        return;
      }
    } catch {}
    setQuoteRes(null); // fall back to preview until Next
  }, [amount, bankCode, account, rateLimitedUntil]);

  // TTL countdown
  React.useEffect(() => {
    if (!quoteRes?.quote?.expiresAt) { setTtlSec(0); return; }
    const expires = new Date(quoteRes.quote.expiresAt).getTime();
    const tick = () => setTtlSec(Math.max(0, Math.floor((expires - Date.now()) / 1000)));
    tick();
    const id = setInterval(tick, 1000);
    return () => clearInterval(id);
  }, [quoteRes?.quote?.expiresAt]);

  // Auto-refresh shortly before expiry when user is idle on this screen
  React.useEffect(() => {
    if (!quoteRes?.quote || ttlSec <= 0) return;
    if (ttlSec <= 5 && !quote.isPending && Date.now() >= rateLimitedUntil) {
      setAutoRefreshing(true);
      quote.mutate({ amountXaf: Number(amount), bankCode, accountNumber: account }, {
        onSettled: () => setAutoRefreshing(false)
      });
    }
  }, [ttlSec, quoteRes?.quote, amount, bankCode, account, rateLimitedUntil]);

  function proceed() {
    const go = (qr: QuoteRes) => {
      try {
        const payload = { quote: qr.quote, recipient: { bankCode, bankName, account, accountName } };
        sessionStorage.setItem('quote:selected', JSON.stringify(payload));
        sessionStorage.removeItem('quote:state');
      } catch {}
      const s = new URLSearchParams({ quoteId: String(qr.quote.id) });
      router.push(`/transfer/confirm?${s.toString()}`);
    };
    // Require a fresh, valid quote to proceed (common fintech pattern)
    if (quoteRes?.quote && ttlSec > 0) { go(quoteRes); return; }
    if (Date.now() < rateLimitedUntil) { setErr('Please wait a few seconds before trying again.'); return; }
    // Force-refresh and proceed on success
    quote.mutate({ amountXaf: Number(amount), bankCode, accountNumber: account }, { onSuccess: (d) => go(d) });
  }
  const receiveNgn = quoteRes?.quote?.receiveNgnMinor ? (quoteRes.quote.receiveNgnMinor / 100).toFixed(2) : "0.00";
  let effectivePreviewRate = previewRate;
  if (!effectivePreviewRate) {
    try {
      const raw = sessionStorage.getItem('rate:last');
      if (raw) {
        const last = JSON.parse(raw) as { rate?: number; at?: number };
        if (last?.rate) effectivePreviewRate = last.rate;
      }
    } catch {}
  }
  const previewReceive = (!quoteRes?.quote && effectivePreviewRate && amount)
    ? (amount * effectivePreviewRate).toFixed(2)
    : null;
  const impliedRate = quoteRes?.quote ? ((quoteRes.quote.receiveNgnMinor / 100) / Math.max(1, quoteRes.quote.amountXaf)).toFixed(2) : null;
  
  return (
    <RequireAuth>
      <div className="min-h-dvh p-6 max-w-2xl mx-auto space-y-6">
        {/* Header */}
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2">
            <button onClick={() => router.back()} aria-label="Back" className="text-xl">‹</button>
            <h1 className="text-lg font-semibold">Transfer To Bank Account</h1>
          </div>
          <button className="text-sm underline" onClick={() => router.push('/transfers')}>History</button>
        </div>

        {/* Recipient summary */}
        <section className="bg-gray-50 border rounded-xl p-3 flex items-center gap-3">
          <div className="h-9 w-9 rounded-full bg-gray-100 flex items-center justify-center text-xs">{(bankName || '•').slice(0,1).toUpperCase()}</div>
          <div className="flex-1 min-w-0">
            <div className="text-sm font-medium truncate">{accountName || "Recipient"}</div>
            <div className="text-xs text-blue-600 truncate">{account}</div>
            <div className="text-xs text-gray-600 truncate">{bankName}</div>
          </div>
        </section>

        {err ? (
          <div className={`text-sm rounded p-2 ${err.includes('wait') ? 'text-gray-700 border border-gray-200' : 'text-red-600 border border-red-200'}`}>{err}</div>
        ) : null}

        {/* Amount card */}
        <section className="border rounded-xl p-4 space-y-4">
          <div className="text-sm font-medium">Amount</div>
          <div>
            <input
              className="w-full px-0 py-2 text-base border-b focus:outline-none focus:ring-0"
              type="text"
              inputMode="numeric"
              value={amountText}
              onChange={(e) => {
                const raw = e.target.value;
                const digits = raw.replace(/[^0-9]/g, "");
                const num = digits ? Number(digits) : 0;
                setAmount(num);
                setAmountText(digits ? nf.format(num) : "");
              }}
              placeholder="XAF 100–5,000,000"
              aria-label="Amount in XAF"
            />
            {quote.isPending && (
              <div className="text-xs text-gray-500 mt-1">Getting quote…</div>
            )}
          </div>
          {/* Quick chips */}
          <div className="grid grid-cols-3 sm:grid-cols-6 gap-2">
            {[5000, 10000, 20000, 50000, 100000, 200000].map((v) => (
              <button key={v}
                type="button"
                className={`h-10 rounded-md border text-sm ${amount===v? 'bg-gray-900 text-white border-gray-900':'bg-white hover:bg-gray-50'}`}
                onClick={() => { setAmount(v); setAmountText(nf.format(v)); }}
              >
                XAF {v.toLocaleString()}
              </button>
            ))}
          </div>
          {/* Rate line */}
          {quoteRes?.quote ? (
            <div className="text-xs text-gray-700 flex items-center gap-2">
              <span className="inline-block h-4 w-4 rounded bg-gray-900" />
              <span>
                {`Rate: 1 XAF = NGN ${(quoteRes.quote.adjustedRate ?? 0).toFixed(2)}`}
              </span>
              {quoteRes.quote.components?.fxMarginBps != null && (
                <span className="text-gray-500">(margin {quoteRes.quote.components.fxMarginBps} bps)</span>
              )}
            </div>
          ) : (
            previewRate ? (
              <div className="text-xs text-gray-700 flex items-center gap-2">
                <span className="inline-block h-4 w-4 rounded bg-gray-900" />
                <span>Rate: 1 XAF = NGN {previewRate.toFixed(2)}</span>
              </div>
            ) : null
          )}
        </section>
        {/* Receive summary and Next */}
        <section className="space-y-3">
          <div className="text-sm">
            <div className="text-gray-800">Receiver gets</div>
            <div className="text-2xl font-bold">
              {quoteRes?.quote
                ? ngnFmt.format(Number(receiveNgn))
                : (previewReceive
                    ? ngnFmt.format(Number(previewReceive))
                    : (amount ? 'Estimating…' : ngnFmt.format(0)))}
            </div>
            {quoteRes?.quote ? (
              <div className="text-xs text-gray-600">Quote expires {new Date(quoteRes.quote.expiresAt).toLocaleTimeString()} ({ttlSec}s left){autoRefreshing ? ' – refreshing…' : ''}</div>
            ) : null}
          </div>
          {/* Basic fee breakdown */}
          {quoteRes?.quote ? (
            <div className="rounded-md border p-3 text-xs text-gray-700 space-y-1">
              <div className="flex justify-between"><span>Send amount</span><span>XAF {nf.format(quoteRes.quote.amountXaf)}</span></div>
              {(() => {
                const c = quoteRes.quote.components || {} as any;
                const fixed = c.fixedFeeXaf ?? null;
                const percent = c.percentFeeXaf ?? null;
                const percentBps = c.percentBps ?? null;
                if (fixed != null || percent != null) {
                  return (
                    <>
                      {fixed != null && (
                        <div className="flex justify-between"><span>Flat fee</span><span>XAF {nf.format(fixed)}</span></div>
                      )}
                      {percent != null && (
                        <div className="flex justify-between"><span>Percent fee{percentBps!=null?` (${(percentBps/100).toFixed(2)}%)`:''}</span><span>XAF {nf.format(percent)}</span></div>
                      )}
                    </>
                  );
                }
                return null;
              })()}
              <div className="flex justify-between"><span>Fees</span><span>XAF {nf.format(quoteRes.quote.feeTotalXaf)}</span></div>
              <div className="flex justify-between"><span>Total to pay</span><span className="font-medium">XAF {nf.format(quoteRes.quote.totalPayXaf)}</span></div>
              <div className="flex justify-between"><span>Effective rate</span><span>{`1 XAF = NGN ${(quoteRes.quote.adjustedRate ?? 0).toFixed(2)}`}</span></div>
            </div>
          ) : (
            (quote.isPending || (previewRate && amount)) ? (
              <div className="rounded-md border p-3 text-xs text-gray-700 space-y-2">
                <div className="h-3 bg-gray-100 rounded animate-pulse" />
                <div className="h-3 bg-gray-100 rounded animate-pulse" />
                <div className="h-3 bg-gray-100 rounded animate-pulse w-2/3" />
              </div>
            ) : null
          )}
          <button
            className="w-full h-12 rounded-full bg-emerald-600 text-white font-medium disabled:opacity-50 disabled:cursor-not-allowed hover:bg-emerald-700 transition-colors"
            disabled={quote.isPending || !amount || !quoteRes?.quote || ttlSec <= 0}
            onClick={proceed}
          >
            {ttlSec <= 0 && amount ? 'Get fresh quote' : 'Next'}
          </button>
        </section>
      </div>
    </RequireAuth>
  );
}
