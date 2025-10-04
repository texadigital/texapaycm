"use client";
import React from "react";
import { useParams, useSearchParams, useRouter } from "next/navigation";
import { useMutation, useQuery } from "@tanstack/react-query";
import http from "@/lib/api";
import PageHeader from "@/components/ui/page-header";

// Minimal success page that celebrates pay-in success and links to receipt/timeline
// It reads display params from the URL (passed by confirm step) and also fetches
// timeline to show the latest status.

type TimelineRes = {
  success: boolean;
  status: string;
  payinStatus?: string;
  payoutStatus?: string;
};

export default function TransferSuccessPage() {
  return (
    <React.Suspense fallback={<div className="min-h-dvh grid place-items-center p-6 text-sm text-gray-600">Loading…</div>}>
      <TransferSuccessInner />
    </React.Suspense>
  );
}

function TransferSuccessInner() {
  const router = useRouter();
  const params = useParams<{ id: string }>();
  const id = params?.id;
  const sp = useSearchParams();

  const [bankName, setBankName] = React.useState(sp.get("bankName") || "");
  const [accountName, setAccountName] = React.useState(sp.get("accountName") || "");
  const [account, setAccount] = React.useState(sp.get("account") || "");
  const [amount, setAmount] = React.useState(Number(sp.get("amount") || 0));
  const [receiveMinor, setReceiveMinor] = React.useState(Number(sp.get("receiveMinor") || 0));

  const { data, refetch, isFetching } = useQuery<TimelineRes>({
    queryKey: ["transfer-success-status", id],
    queryFn: async () => {
      const res = await http.get(`/api/mobile/transfers/${id}/timeline`);
      return res.data;
    },
    enabled: !!id,
    staleTime: 5000,
  });

  const [payoutStatus, setPayoutStatus] = React.useState<string | undefined>(undefined);
  const [payoutError, setPayoutError] = React.useState<string | null>(null);

  // Initiate payout
  const initiate = useMutation({
    mutationFn: async () => {
      setPayoutError(null);
      const res = await http.post(`/api/mobile/transfers/${id}/payout`);
      return res.data as any;
    },
    onError: (e: any) => setPayoutError(e?.response?.data?.message || e.message),
  });

  // Poll payout status
  const poll = useMutation({
    mutationFn: async () => {
      const res = await http.post(`/api/mobile/transfers/${id}/payout/status`);
      return res.data as { status: string };
    },
    onSuccess: async (d) => {
      setPayoutStatus(d.status);
      // Keep the timeline in sync
      await refetch();
    },
    onError: (e: any) => setPayoutError(e?.response?.data?.message || e.message),
  });

  // Drive payout lifecycle: initiate once when eligible; poll until terminal
  React.useEffect(() => {
    if (!id) return;
    const payinOk = data?.payinStatus === 'success';
    const payoutOk = data?.payoutStatus === 'success';
    const payoutProc = data?.payoutStatus === 'processing' || data?.status === 'payout_pending';
    setPayoutStatus(data?.payoutStatus);
    if (payinOk && !payoutOk && !initiate.isPending && initiate.status === 'idle' && !payoutProc) {
      initiate.mutate();
    }
  }, [id, data?.payinStatus, data?.payoutStatus, data?.status]);

  React.useEffect(() => {
    if (!id) return;
    if (payoutStatus === 'success' || payoutStatus === 'failed') return; // terminal
    // Poll every 6s while not terminal
    const t = setInterval(() => poll.mutate(), 6000);
    return () => clearInterval(t);
  }, [id, payoutStatus]);

  // Notify other pages (dashboard) when payout is completed to refresh their data
  React.useEffect(() => {
    if ((payoutStatus || data?.payoutStatus) === 'success') {
      try { window.dispatchEvent(new CustomEvent('transfers:refresh')); } catch {}
    }
  }, [payoutStatus, data?.payoutStatus]);

  // Server fallback for amounts/recipient when URL params are missing or zero
  React.useEffect(() => {
    (async () => {
      if (!id) return;
      const needs = !receiveMinor || !amount || !bankName || !account || !accountName;
      if (!needs) return;
      try {
        const res = await http.get(`/api/mobile/transfers/${id}`);
        const d = res.data || {};
        if (d?.recipientGetsMinor) setReceiveMinor(Number(d.recipientGetsMinor || 0));
        if (typeof d?.amountXaf === 'number') setAmount(Number(d.amountXaf || 0));
        if (d?.accountName) setAccountName(d.accountName);
        if (d?.bankName) setBankName(d.bankName);
        if (d?.accountNumber) setAccount(d.accountNumber);
      } catch (e: any) {
        const status = e?.response?.status;
        if (status === 403) {
          try { router.replace('/transfers'); } catch {}
        }
      }
    })();
  }, [id]);

  const receiveNgn = (receiveMinor || 0) / 100;

  return (
    <div className="min-h-dvh p-6 max-w-md mx-auto space-y-6">
      <PageHeader title="Transfer successful" />

      <div className="flex items-center justify-center">
        <div className="h-16 w-16 rounded-full bg-blue-600 text-white grid place-items-center text-3xl">✓</div>
      </div>

      <div className="text-center space-y-1">
        <div className="text-3xl font-semibold">₦ {receiveNgn.toFixed(2)}</div>
        <p className="text-gray-600">
          The recipient account is expected to be credited shortly, subject to bank notification.
        </p>
      </div>

      <div className="border rounded p-3 text-sm space-y-1">
        <div className="font-medium">{accountName || "Recipient"}</div>
        <div className="text-gray-600">{bankName} • {account}</div>
        {data ? (
          <div className="text-xs text-gray-600 flex items-center gap-2">
            <span>Pay‑in: <span className="capitalize">{data.payinStatus || '—'}</span></span>
            <span>•</span>
            <span>Payout: <span className="capitalize">{payoutStatus || data.payoutStatus || '—'}</span></span>
            <button
              className="ml-auto text-xs underline disabled:opacity-50"
              onClick={() => refetch()}
              disabled={isFetching}
            >{isFetching ? 'Refreshing…' : 'Refresh'}</button>
          </div>
        ) : null}
        {payoutError ? (
          <div className="text-xs text-red-600 border border-red-200 rounded p-2 mt-2">{payoutError}</div>
        ) : null}
      </div>

      <div className="grid grid-cols-2 gap-3">
        <a className="text-center border rounded px-4 py-2 disabled:opacity-50"
           href={`/transfer/${id}/receipt`}
           aria-disabled={(payoutStatus || data?.payoutStatus) !== 'success'}
           onClick={(e) => {
             if ((payoutStatus || data?.payoutStatus) !== 'success') e.preventDefault();
           }}
        >
          Share receipt
        </a>
        <a className="text-center border rounded px-4 py-2" href={`/transfer/${id}/timeline`}>
          View details
        </a>
      </div>

      {/* Manual controls (hidden behind success UI) */}
      {data && data.payinStatus === 'success' && (payoutStatus !== 'success') && (
        <div className="text-center text-xs text-gray-600">
          {initiate.isPending ? 'Starting payout…' : (
            <button className="underline" onClick={() => initiate.mutate()} disabled={initiate.isPending}>Re-initiate payout</button>
          )}
        </div>
      )}
    </div>
  );
}
