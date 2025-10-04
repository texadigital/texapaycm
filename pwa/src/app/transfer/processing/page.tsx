"use client";
import React from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { useMutation } from "@tanstack/react-query";
import http from "@/lib/api";
import RequireAuth from "@/components/guards/require-auth";

export default function ProcessingPage() {
  const router = useRouter();
  const sp = useSearchParams();
  const transferId = Number(sp.get("transferId") || 0);

  const [status, setStatus] = React.useState<string>("pending"); // pay-in
  const [payoutStatus, setPayoutStatus] = React.useState<string | null>(null);
  const [error, setError] = React.useState<string | null>(null);
  const [refundInfo, setRefundInfo] = React.useState<{ id?: string; status?: string } | null>(null);
  const selectedRef = React.useRef<{ quote?: any; recipient?: { bankCode: string; bankName: string; account: string; accountName: string } } | null>(null);

  React.useEffect(() => {
    try {
      const raw = sessionStorage.getItem('quote:selected');
      if (raw) selectedRef.current = JSON.parse(raw);
    } catch {}
  }, []);

  // Single timeline poller (more robust, fewer calls)
  const pollTimeline = useMutation({
    mutationFn: async () => {
      // Shorter timeout per request; treat timeouts as transient
      const res = await http.get(`/api/mobile/transfers/${transferId}/timeline`, { timeout: 10000 });
      return res.data as { status?: string; payinStatus?: string; payoutStatus?: string };
    },
    onSuccess: (d) => {
      if (!d) return;
      if (d.payinStatus) setStatus(d.payinStatus);
      if (d.payoutStatus != null) setPayoutStatus(d.payoutStatus);
    },
    onError: (e: any) => {
      // Ignore timeouts to keep polling silently
      if (String(e?.message || "").includes("timeout")) return;
      setError(e?.response?.data?.message || e.message);
    },
  });

  React.useEffect(() => {
    if (!transferId) return;
    const id = setInterval(() => pollTimeline.mutate(), 3000);
    pollTimeline.mutate(); // fire immediately
    return () => clearInterval(id);
  }, [transferId]);

  React.useEffect(() => {
    if (status === "failed" || status === "error") {
      router.replace(`/transfer/${transferId}/failed`);
      return;
    }
    // If pay-in is successful and payout already terminal, redirect quickly
    if (status === "success" || status === "completed") {
      if (payoutStatus === "success" || payoutStatus === "completed") {
        const q = selectedRef.current?.quote;
        const r = selectedRef.current?.recipient;
        const sp = new URLSearchParams({
          bankName: r?.bankName || '',
          accountName: r?.accountName || '',
          account: r?.account || '',
          amount: q?.amountXaf ? String(q.amountXaf) : '0',
          receiveMinor: q?.receiveNgnMinor ? String(q.receiveNgnMinor) : '0',
        });
        router.replace(`/transfer/${transferId}/success?${sp.toString()}`);
      }
    }
  }, [status, payoutStatus, transferId]);

  React.useEffect(() => {
    if (payoutStatus === "failed" || payoutStatus === "error") {
      // Try to fetch refund info once, show banner for a moment, then redirect
      (async () => {
        try {
          const res = await http.get(`/api/mobile/transfers/${transferId}`);
          const d = res.data || {};
          if (d?.refundId) setRefundInfo({ id: d.refundId, status: d.refundStatus });
        } catch {}
        setTimeout(() => router.replace(`/transfer/${transferId}/failed`), 1500);
      })();
    }
  }, [payoutStatus, transferId]);

  return (
    <RequireAuth>
      <div className="min-h-dvh p-6 max-w-xl mx-auto space-y-6">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2">
            <button onClick={() => router.back()} aria-label="Back" className="text-xl">‹</button>
            <h1 className="text-lg font-semibold">Processing Payment</h1>
          </div>
        </div>

        {error ? <div className="text-sm text-red-600 border border-red-200 rounded p-2">{error}</div> : (
          <div className="border rounded-xl p-4 space-y-2 text-sm">
            <div className="flex items-center gap-2">
              <svg className="animate-spin h-4 w-4" viewBox="0 0 24 24">
                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
              </svg>
              <span>We’re confirming your payment…</span>
            </div>
            <div className="text-xs text-gray-600">Current status: {status}</div>
            {(status === 'success' || status === 'completed') && (
              <div className="text-xs text-gray-700">Payout status: {payoutStatus || 'pending'} (auto-updating…)</div>
            )}
            {payoutStatus && /failed|error/i.test(payoutStatus) && (
              <div className="text-xs text-rose-700 bg-rose-50 border border-rose-200 rounded p-2">
                Payout failed. {refundInfo?.id ? (
                  <>Refund initiated: <span className="font-mono">{refundInfo.id}</span> ({refundInfo.status || 'initiated'})</>
                ) : 'Initiating refund…'}
              </div>
            )}
            <div className="pt-2 text-xs text-gray-700">
              If prompted by your operator, dial the USSD to approve:
              <ul className="list-disc pl-5">
                <li>MTN: *126#</li>
                <li>Orange: *150#</li>
              </ul>
            </div>
          </div>
        )}
      </div>
    </RequireAuth>
  );
}
