"use client";
import React from "react";
import { useRouter, useParams } from "next/navigation";
import { useMutation } from "@tanstack/react-query";
import RequireAuth from "@/components/guards/require-auth";
import http from "@/lib/api";

export default function TransferFailedPage() {
  const router = useRouter();
  const params = useParams();
  const id = Number(params?.id || 0);

  const [error, setError] = React.useState<string | null>(null);
  const [details, setDetails] = React.useState<any>(null);
  const friendly = React.useMemo(() => {
    const codeMap: Record<string, string> = {
      '51': 'Insufficient funds at beneficiary bank',
      '54': 'Expired instrument',
      '05': 'Do not honor',
      '91': 'Issuer or switch inoperative',
      '96': 'System malfunction',
    };
    const raw = details?.lastPayoutError as string | undefined;
    const explicit = (details?.lastPayoutProviderCode || details?.lastPayoutCode || details?.providerCode || '') as string;
    let code = String(explicit || '');
    if (!code && raw) {
      const m = raw.match(/code\s*([A-Za-z0-9]+)/i) || raw.match(/responseCode["']?:\s*['"]?([A-Za-z0-9]+)/i);
      if (m) code = m[1];
    }
    const msg = code ? (codeMap[code] || `Payment provider declined (code ${code})`) : undefined;
    return { code, msg };
  }, [details?.lastPayoutError, details?.lastPayoutProviderCode, details?.lastPayoutCode, details?.providerCode]);

  const load = useMutation({
    mutationFn: async () => {
      const res = await http.get(`/api/mobile/transfers/${id}`);
      return res.data as any;
    },
    onSuccess: (d) => setDetails(d),
    onError: (e: any) => setError(e?.response?.data?.message || e.message),
  });

  React.useEffect(() => { if (id) load.mutate(); }, [id]);

  // Auto-refresh refund status every 6s until terminal
  React.useEffect(() => {
    const status = details?.refundStatus || '';
    const terminal = /completed|success|failed|error/i.test(status);
    if (!details || terminal) return;
    const t = setInterval(() => load.mutate(), 6000);
    return () => clearInterval(t);
  }, [details?.refundStatus]);

  const recipient = details ? {
    bankCode: details.bankCode,
    bankName: details.bankName,
    account: details.accountNumber,
    accountName: details.accountName,
  } : null;

  return (
    <RequireAuth>
      <div className="min-h-dvh p-6 max-w-xl mx-auto space-y-6">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2">
            <button onClick={() => router.back()} aria-label="Back" className="text-xl">‹</button>
            <h1 className="text-lg font-semibold">Transfer Failed</h1>
          </div>
        </div>

        {error ? <div className="text-sm text-red-600 border border-red-200 rounded p-2">{error}</div> : null}

        <div className="border rounded-xl p-4 space-y-2">
          <div className="text-sm">We couldn’t complete your transfer.</div>
          {details?.lastPayoutError ? (
            <div className="text-sm text-gray-700">Reason: {details.lastPayoutError}</div>
          ) : null}
          {friendly.msg && (
            <div className="text-xs text-gray-700">Provider: {friendly.msg}</div>
          )}
          <div className="text-xs text-gray-600">Transfer ID: {id}</div>
          {details?.refundId && (
            <div className="text-sm mt-1">
              <div className="text-gray-700">Refund: <span className="font-medium">{String(details.refundStatus || 'initiated')}</span></div>
              <div className="text-xs text-gray-500">Refund ID: <span className="font-mono">{details.refundId}</span></div>
            </div>
          )}
        </div>

        <div className="space-y-2">
          <button
            className="w-full h-12 rounded-full bg-emerald-600 text-white font-medium"
            onClick={() => {
              if (recipient) {
                const sp = new URLSearchParams({
                  bankCode: recipient.bankCode || '',
                  bankName: recipient.bankName || '',
                  account: recipient.account || '',
                  accountName: recipient.accountName || '',
                });
                router.replace(`/transfer/quote?${sp.toString()}`);
              } else {
                router.replace('/transfer/verify');
              }
            }}
          >
            Try Again
          </button>
          <button className="w-full h-12 rounded-full bg-gray-900 text-white" onClick={() => router.replace('/transfers')}>
            Go to Transfers
          </button>
        </div>
      </div>
    </RequireAuth>
  );
}
