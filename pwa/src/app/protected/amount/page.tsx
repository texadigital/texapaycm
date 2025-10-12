"use client";
import React from "react";
import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import RequireAuth from "@/components/guards/require-auth";
import http from "@/lib/api";

export default function ProtectedAmountPage() {
  return (
    <RequireAuth>
      <ProtectedAmountInner />
    </RequireAuth>
  );
}

function ProtectedAmountInner() {
  const router = useRouter();
  const params = useSearchParams();
  const bankCode = params.get("bankCode") || "";
  const bankName = params.get("bankName") || "";
  const account = params.get("account") || "";
  const accountName = params.get("accountName") || "";

  const [amount, setAmount] = React.useState("");
  const [error, setError] = React.useState<string | null>(null);
  const [busy, setBusy] = React.useState(false);
  const [cooldownUntil, setCooldownUntil] = React.useState<number>(0);
  const [cooldownLeft, setCooldownLeft] = React.useState<number>(0);

  async function startProtected() {
    setError(null);
    const minor = Math.round(Number(amount) * 100);
    if (!minor || minor < 100) { setError("Enter a valid amount (>= 1.00)"); return; }
    if (!bankCode || !account) { setError("Missing recipient. Go back and verify account."); return; }
    setBusy(true);
    try {
      const res = await http.post('/api/mobile/protected/init', {
        receiver: { bankCode, accountNumber: account },
        amountNgnMinor: minor,
      });
      const d = res.data || {};
      const ref = d.ref as string | undefined;
      if (!ref) throw new Error("Missing reference from server");
      router.push(`/protected/${ref}`);
    } catch (e: any) {
      const status = e?.response?.status;
      if (status === 429) {
        const ra = Number(e?.response?.headers?.['retry-after']) || 15; // seconds fallback
        const until = Date.now() + Math.max(5, Math.min(120, ra)) * 1000;
        setCooldownUntil(until);
        setError('Too Many Attempts. Please wait and try again.');
      } else {
        setError(e?.response?.data?.message || e?.message || 'Failed to start protected transfer');
      }
    } finally {
      setBusy(false);
    }
  }

  // Cooldown countdown tick
  React.useEffect(() => {
    if (cooldownUntil <= Date.now()) { setCooldownLeft(0); return; }
    const tick = () => setCooldownLeft(Math.max(0, Math.ceil((cooldownUntil - Date.now())/1000)));
    tick();
    const id = setInterval(tick, 500);
    return () => clearInterval(id);
  }, [cooldownUntil]);

  return (
    <div className="min-h-dvh p-6 max-w-md mx-auto space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-2">
          <button onClick={() => router.back()} aria-label="Back" className="text-xl">‹</button>
          <h1 className="text-lg font-semibold">Enter Amount</h1>
        </div>
        <Link href="/protected/verify" className="text-sm underline">Change recipient</Link>
      </div>

      {/* Recipient summary */}
      <section className="border rounded p-4 text-sm">
        <div className="text-gray-500">Recipient</div>
        <div className="font-medium">{accountName || account}</div>
        <div className="text-gray-600">{bankName || bankCode} · {account}</div>
      </section>

      {/* Amount input */}
      <section className="border rounded p-4 space-y-2">
        <label className="block text-sm text-gray-600">Amount (NGN)</label>
        <input
          className="w-full border rounded px-3 py-2"
          inputMode="decimal"
          placeholder="e.g. 2500.00"
          value={amount}
          onChange={(e) => setAmount(e.target.value)}
        />
        {error && (
        <div className="text-sm text-red-600 mt-1">
          {error} {cooldownLeft>0 ? `(wait ${cooldownLeft}s)` : null}
        </div>
      )}
      </section>

      <button
        className="w-full h-12 rounded-full bg-emerald-600 text-white font-medium disabled:opacity-50 disabled:cursor-not-allowed hover:bg-emerald-700 transition-colors"
        onClick={startProtected}
        disabled={busy || cooldownLeft>0}
      >
        {busy ? 'Starting…' : (cooldownLeft>0 ? `Wait ${cooldownLeft}s` : 'Start Protected')}
      </button>
    </div>
  );
}
