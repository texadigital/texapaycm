"use client";
import React from "react";
import { useParams } from "next/navigation";
import Link from "next/link";
import { useMutation, useQuery } from "@tanstack/react-query";
import http from "@/lib/api";
import PageHeader from "@/components/ui/page-header";
import { CardSkeleton } from "@/components/ui/skeleton";

type TimelineItem = { at: string; message?: string; state?: string };

type TimelineRes = {
  success: boolean;
  timeline: TimelineItem[];
  status: string;
  payinStatus?: string;
  payoutStatus?: string;
};

type TransferDetails = {
  id: number;
  reference?: string;
  status: string;
  createdAt?: string;
  amountXaf?: number;
  feeTotalXaf?: number;
  totalPayXaf?: number;
  receiveNgnMinor?: number;
  adjustedRate?: number;
  rateDisplay?: string | null;
  recipientGetsMinor?: number;
  recipientGetsCurrency?: string;
  sourceCurrency?: string;
  targetCurrency?: string;
  payerMsisdn?: string;
  bankName?: string;
  accountNumber?: string;
  accountName?: string;
  payinAt?: string | null;
  payoutInitiatedAt?: string | null;
  payoutAttemptedAt?: string | null;
  payoutCompletedAt?: string | null;
  payinRef?: string | null;
  payoutRef?: string | null;
  nameEnquiryRef?: string | null;
  transactionNo?: string;
  sessionId?: string | null;
  lastPayoutError?: string | null;
};

export default function TransferTimelinePage() {
  const params = useParams<{ id: string }>();
  const id = params?.id;

  const { data, isLoading, error, refetch, isFetching } = useQuery<TimelineRes>({
    queryKey: ["transfer-timeline", id],
    queryFn: async () => {
      const res = await http.get(`/api/mobile/transfers/${id}/timeline`);
      return res.data;
    },
    enabled: !!id,
  });

  const details = useQuery<TransferDetails>({
    queryKey: ["transfer-details", id],
    queryFn: async () => {
      const res = await http.get(`/api/mobile/transfers/${id}`);
      return res.data as any;
    },
    enabled: !!id,
  });

  type StatusRes = { status?: string; success?: boolean };

  const [payoutStatus, setPayoutStatus] = React.useState<string | null>(null);

  const payout = useMutation({
    mutationFn: async () => {
      const res = await http.post(`/api/mobile/transfers/${id}/payout`);
      return res.data as StatusRes;
    },
    onSuccess: (d) => setPayoutStatus(d.status || 'pending'),
  });

  const payoutPoll = useMutation({
    mutationFn: async () => {
      const res = await http.post(`/api/mobile/transfers/${id}/payout/status`);
      return res.data as StatusRes;
    },
    onSuccess: (d) => setPayoutStatus(d.status || null),
  });

  function fmtMoney(minor: number, currency: string) {
    const divisor = 100;
    const symbol = currency === "NGN" ? "₦" : currency === "XAF" ? "XAF " : `${currency} `;
    return `${symbol}${(minor / divisor).toLocaleString(undefined, { maximumFractionDigits: 2 })}`;
  }

  const statusLabel = React.useMemo(() => {
    const s = details.data?.status || data?.status || "";
    const payinOk = !!details.data?.payinAt;
    const payoutDone = !!details.data?.payoutCompletedAt;
    const payoutInProgress = !!details.data?.payoutInitiatedAt || !!details.data?.payoutAttemptedAt;
    if (s.includes('failed')) return 'Failed';
    if (payinOk && payoutDone) return 'Successful';
    if (payinOk && payoutInProgress) return 'Pending';
    return s ? s.replaceAll('_',' ') : 'Pending';
  }, [details.data, data]);

  const stepTimes = {
    payin: details.data?.payinAt || null,
    processing: details.data?.payoutInitiatedAt || details.data?.payoutAttemptedAt || null,
    received: details.data?.payoutCompletedAt || null,
  };

  const ngnMinor = details.data?.recipientGetsMinor ?? details.data?.receiveNgnMinor ?? 0;
  const ngnAmount = fmtMoney(ngnMinor, details.data?.recipientGetsCurrency || 'NGN');

  function copy(text?: string | null) {
    if (!text) return;
    try { navigator.clipboard.writeText(text); } catch {}
  }

  return (
    <div className="min-h-dvh p-6 max-w-2xl mx-auto space-y-4">
      <PageHeader title="Transaction Details">
        <button className="border rounded px-3 py-1" onClick={() => refetch()} disabled={isFetching}>
          {isFetching ? "Refreshing..." : "Refresh"}
        </button>
      </PageHeader>

      {isLoading && (
        <div className="space-y-3">
          <CardSkeleton lines={2} />
          <CardSkeleton lines={4} />
        </div>
      )}
      {error && (
        <div className="text-sm text-red-600 border border-red-200 rounded p-2">
          {(error as any)?.response?.data?.message || (error as Error).message}
        </div>
      )}

      {data && (
        <div className="space-y-4">
          {/* Header card */}
          <div className="rounded border p-4 text-center space-y-2">
            <div className="text-sm text-gray-600">Transfer to {details.data?.accountName || '—'}</div>
            <div className="text-2xl font-extrabold">{ngnAmount}</div>
            <div className={`inline-block text-xs px-2 py-0.5 rounded ${statusLabel === 'Successful' ? 'bg-emerald-50 text-emerald-700' : statusLabel === 'Failed' ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700'}`}>{statusLabel}</div>
            {/* Steps */}
            <div className="pt-4 grid grid-cols-3 gap-2 text-sm">
              {[
                { key: 'Payment successful', time: stepTimes.payin },
                { key: 'Processing by bank', time: stepTimes.processing },
                { key: 'Received by bank', time: stepTimes.received },
              ].map((s, idx) => (
                <div key={idx} className="flex flex-col items-center gap-1">
                  <div className={`w-6 h-6 rounded-full flex items-center justify-center ${s.time ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'}`}>{s.time ? '✓' : '•'}</div>
                  <div className="text-center text-xs text-gray-700 leading-tight">
                    <div>{s.key}</div>
                    <div className="text-gray-500" suppressHydrationWarning>{s.time ? new Date(s.time).toLocaleString() : '—'}</div>
                  </div>
                </div>
              ))}
            </div>
          </div>

          {/* Info note bubble */}
          <div className="rounded border p-3 text-xs text-gray-700 bg-gray-50">
            The recipient account is expected to be credited within 5 minutes, subject to notification by the bank.
          </div>

          {/* Amount box */}
          <div className="rounded border p-3 text-sm">
            <div className="flex items-center justify-between py-1">
              <div>Amount</div>
              <div className="font-semibold">{details.data?.amountXaf?.toLocaleString()} XAF</div>
            </div>
            <div className="flex items-center justify-between py-1">
              <div>Fee</div>
              <div className="font-semibold">{(details.data?.feeTotalXaf ?? 0).toLocaleString()} XAF</div>
            </div>
            <div className="flex items-center justify-between py-1">
              <div>Amount Paid</div>
              <div className="font-semibold">{details.data?.totalPayXaf?.toLocaleString()} XAF</div>
            </div>
            {details.data?.rateDisplay ? (
              <div className="flex items-center justify-between py-1">
                <div>Exchange Rate Used</div>
                <div className="font-semibold">{details.data.rateDisplay}</div>
              </div>
            ) : null}
            {ngnMinor ? (
              <div className="flex items-center justify-between py-1">
                <div>Recipient Gets</div>
                <div className="font-semibold">{fmtMoney(ngnMinor, details.data?.recipientGetsCurrency || 'NGN')}</div>
              </div>
            ) : null}
          </div>

          {/* Transaction Details */}
          <div className="rounded border p-3 text-sm space-y-2">
            <div>
              <div className="text-xs text-gray-500">Recipient Details</div>
              <div className="font-medium">{details.data?.accountName || '—'}</div>
              <div className="text-gray-700">{details.data?.bankName || '—'} | {details.data?.accountNumber || '—'}</div>
            </div>
            <div className="flex items-center justify-between">
              <div className="text-gray-700">Transaction No.</div>
              <div className="flex items-center gap-2">
                <div className="font-mono text-xs">{details.data?.transactionNo || details.data?.id}</div>
                <button className="text-xs underline" onClick={() => copy(details.data?.transactionNo || String(details.data?.id))}>Copy</button>
              </div>
            </div>
            <div className="flex items-center justify-between">
              <div className="text-gray-700">Payment Method</div>
              <div className="font-medium">Mobile Money</div>
            </div>
            <div className="flex items-center justify-between">
              <div className="text-gray-700">Transaction Date</div>
              <div className="font-medium" suppressHydrationWarning>{details.data?.createdAt ? new Date(details.data.createdAt).toLocaleString() : '—'}</div>
            </div>
            <div className="flex items-center justify-between">
              <div className="text-gray-700">Session ID</div>
              <div className="flex items-center gap-2">
                <div className="font-mono text-xs">{details.data?.sessionId || '—'}</div>
                <button className="text-xs underline" onClick={() => copy(details.data?.sessionId || undefined)}>Copy</button>
              </div>
            </div>
          </div>

          {/* Actions */}
          <div className="rounded border p-3 text-sm space-y-3">
            <div className="flex items-center justify-between">
              <div>Category</div>
              <div className="text-gray-700">Transfer</div>
            </div>
            <div>
              <Link
                href={`/transfer/verify?${new URLSearchParams({ bankCode: details.data?.bankName ? ('' + (details.data as any).bankCode) : (details.data as any)?.bankCode || '', bankName: details.data?.bankName || '', account: details.data?.accountNumber || '', accountName: details.data?.accountName || '' }).toString()}`}
                className="inline-flex items-center gap-1 text-emerald-700"
              >
                <span>＋</span>
                <span>Transfer Again</span>
              </Link>
            </div>
          </div>

          <div className="flex items-center justify-between gap-3">
            <Link href={`/transfer/${id}/support`} className="flex-1 border rounded px-3 py-2 text-center">Report Issue</Link>
            <Link href={`/transfer/${id}/receipt`} className="flex-1 bg-black text-white rounded px-3 py-2 text-center">Share Receipt</Link>
          </div>
          {details.data?.lastPayoutError ? (
            <div className="text-xs text-rose-700 border border-rose-200 rounded p-2">{details.data.lastPayoutError}</div>
          ) : null}
        </div>
      )}
    </div>
  );
}
